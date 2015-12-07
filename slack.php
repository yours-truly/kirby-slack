<?php

class slack {

  /**
   * Download a binary file and write it to disk.
   */
  private static function download($url, $file) {
    $fp = fopen($file, 'w+');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
  }

  /**
   * Invoke a Slack API method.
   */
  private static function api($method, $params) {
    return json_decode(remote::post('https://slack.com/api/' . $method,
      [
        'data' => array_merge($params, [
          'token' => c::get('slack.auth'),
        ])
      ]
    ), true);
  }

  /**
   * Convert Slack's internal message format to HTML.
   */
  private static function format($s) {
    $s = preg_replace_callback('/<(@.|#.|!)?(.*?)(?:\|(.*?))?>/', function ($m) {
      if ($m[1]) return empty($m[3]) ? $m[2] : $m[3];
      return '<a href="' . $m[2] . '">' . (empty($m[3]) ? $m[2] : $m[3]) . '</a>';
    }, $s);
    $s = preg_replace('/_(.+?)_/', '<i>$1</i>', $s);
    $s = preg_replace('/\*(.+?)\*/', '<b>$1</b>', $s);
    return $s;
  }

  /**
   * Handle an incomming request.
   */
  public static function handle($pageId, $lang) {

    if (r::data('token') != c::get('slack.verify')) {
      return response::error('Forbidden', 403);
    }

    $history = static::api('channels.history', [
      'channel' => r::data('channel_id')
    ]);

    if (!empty($history['error'])) {
      // Something went wrong ... maybe:
      $msg = [
        'channel_not_found' => ':lock: Sorry, but this is a private channel'
      ];
      $err = $history['error'];
      return response::json(isset($msg[$err]) ? $msg[$err] : $err);
    }

    $messages = $history['messages'];

    if (!empty(r::data('text'))) {
      $messages = array_values(array_filter($messages, function($m) {
        return stristr($m['text'], r::data('text'));
      }));
    }

    if (empty($messages)) {
      return response::json(":mag: Sorry, I couldn't find the post you're looking for");
    }

    $m = $messages[0];

    $a = @$m['attachments'][0];
    $img = @$a['image_url'];
    if (empty($img)) $img = @$a['thumb_url'];

    if (empty($img)) {
      return response::json(":warning: I'll only publish posts with images");
    }

    $page = site()->visit($pageId, $lang);

    $dir = $page->root();
    $ext = preg_replace('/.+?(\.\w+)($|[#?].*)/', '$1', $img);
    $file = $dir . DS . $m['ts'] . $ext;

    // Output success message early because of short slackbot timeouts
    $msg = ':metal: *' . r::data('text', 'last')
      . '* post is now live'
      . ' on <'. $page->url() . '>';

    echo $msg;
    flush();
    error_log($msg);

    $user = static::api('users.info', [ 'user' => $m['user'] ]);

    $meta = [
      'title' => $a['title'],
      'date' => date('d.m.Y', $m['ts']),
      'description' => @$a['text'],
      'linkUrl' => $a['from_url'],
      'author' => $user['user']['profile']['real_name'],
      'avatar' => $m['user'] . '.jpg',
      'comment' => static::format(@$m['text'])
    ];

    data::write($file.'.txt', $meta, 'kd');

    // Download the avatar image
    $avatar = $dir . DS . $meta['avatar'];
    static::download($user['user']['profile']['image_72'], $avatar);

    // Download the image
    static::download($img, $file);

    // Response has already been sent
    return false;
  }
}

kirby()->routes([
  [
    'pattern' => c::get('slack.route', 'hooks/slack/(:all)'),
    'method' => 'POST',
    'action' => function($page) {
      $m = [];
      // Extract the optional language suffix
      preg_match('/(.*?)(?:\.(.+?))?$/', $page, $m);
      return slack::handle($m[1], $m[2]);
    }
  ]
]);
