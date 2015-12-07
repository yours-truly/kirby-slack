# Kirby plugin to /publish Slack posts on the web

This plugin lets Slack users publish posts from any (non-private) Slack channel to a Kirby website.

## Installation

Download the plugin from GitHub and put it in your `site/plugins` folder.

## Slack integration

Goto to your team's Slack integrations page and set choose __Slash Commands__ from the _DIY Integrations & Customizations_ section.

Set up the integration like this:

![Screenshot](settings.png)

The __URL__ field must point to your website and include the name
(and optionally the language) of the page where the posts should be
stored. The format is:

`http(s)://<domain>.<tld>/hooks/slack/<page-name>[.lang]`

Copy the generated __Token__ and add it to your `config.php`:

```php
c::set('slack.verify', '<insert your verification token here>');
c::set('slack.auth', '<insert your auth token here>');
```

The `slack.auth` token can be obtained by visiting https://api.slack.com/web

With this _full-access token_ the plugin will query the Slack Web API to retrieve the actual post and its attachments.

## Kirby integration

The plugin (when set up as Slack command integration) will look for the last post that contains the given _keyword_ and download the first attached image or video thumbnail.

__Note:__ If the post doesn't have any media attached, the Slackbot will refuse to publish it.

Along side the downloaded image a `.txt` will be stored that contains the
following information:

* `Date` – The post's formatted date (DD.MM.YYYY)
* `LinkUrl` – The external link
* `Title` – The attachment's title
* `Description` – The meta description of the linked page
* `Author` – The real name of the user who created the post
* `Avatar` – The name of the author's avatar image (`<user-id>.jpg`)
* `Comment` – The posts text (containing the link) converted into HTML
* `Slack` – A marker that is always set to `1` and can be used to identify Slack posts (see below)

In your templates you can use the following code to retrieve all published Slack posts:

```php
$posts = $page->images()->filterBy('slack', 1)->sortBy('filename', 'desc');
```

## Kirby configuration

In order to make things work you need to provide some settings in your project's `config.php`:

* `slack.route` – The URL called by the Slack integration. Defaults to `'hooks/slack'`
* `slack.page` – The id of an existing page where the posts are stored. _(Required)_ 
* `slack.verify` – The token that was generated (or manually entered) during the Slack integration setup. _(Required)_
* `slack.auth` – The secret auth token that is used to query the Slack API. _(Required)_

## Panel blueprint

In order to edit posts via the Kirby panel you can add the following file-fields to the blueprint of your page:

```yaml
files:
  fields:
    title:
      label: Title
      type: text
    slack:
      type: hidden
    description:
      label: Description
      type: textarea
    link:
      label: Link
      type: url
    author:
      label: Author
      type: text
    avatar:
      type: hidden
    Comment:
      label: Comment
      type: textarea
```

## Slack integration

Goto to your team's Slack integrations page and set up a command
that POSTs to http(s)://example.com/hooks/slack.

## Ideas

For now the plugin perfectly fits our needs. There are some additional ideas though that might get implemented in the future. These are:

* Restrict `/publish` to certain users
* Add an option so that people can only publish their own posts
* Add an `/unpublish` command
* Also search the attachment's text/title/link for the given _keyword_, not only the post text entered by the user.

## License

MIT
