USER:
    <?=$login?>
 <br>
<? if ( !$this->params['access'] == '' ) : ?>
    <h3>Access to  <?=$this->params['access']?> 
        action for - <?=$login?> - isn't allowed!</h3>
<? endif ?>

<a href="/article/Add">Add</a>

<ul>
    <?php
    foreach ( $articles as $article):?>

    <li><?=$article['id_article']?>.<b><?=$article['title']?></b>
    	<br><b>Intro:</b> <?=M_Data::articles_intro($article,100)?>
    	<br><b>Views:</b> <?=$article['views']?>
        <br><b>Comments:</b><br><span class = "comment">
            <?php if ( !$article['comment'] == '') :?><?=$article['comment']?>
            <?php else :?>There are no comments yet.<?php endif;?>
        </span>

            <br>
            <a href="/article/Look/<?=$article['id_article']?>">Look</a> |
            <a href="/article/Edit/<?=$article['id_article']?>">Edit</a> |
            <a href="/article/Delete/<?=$article['id_article']?>">Delete</a> |
            <a href="/article/Comment/<?=$article['id_article']?>">Comment</a>

    </li>

<?php endforeach;?>
</ul>