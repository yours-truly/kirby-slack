<?php foreach($page->images()->filterBy('slack', '1')->sortBy('filename', 'desc') as $post): ?>
  <div class="slack">
    <img class="slack__avatar" src="<?php echo $page->image($post->avatar())->url() ?>">
    <div class="slack__post">
      <div class="slack__meta">
        <span class="slack__author"><?php echo $post->author()->html() ?></span>
        <span class="slack__date"><?php echo $post->date()->html() ?></span>
      </div>
      <div class="slack__comment">
        <?php echo $post->comment()->html() ?>
      </div>
      <div class="slack__attachment">
        <a class="slack__title" href="<?php echo $post->linkurl()->url() ?>"><?php echo $post->title()->html() ?></a>
        <?php echo $post->description()->html() ?>
        <img class="slack__image" src="<?php echo $post->url() ?>">
      </div>
    </div>
  </div>
<?php endforeach ?>
