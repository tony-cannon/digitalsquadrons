<?php 

//$competitionGroups =  BP_Groups_Group::get( array( 'type'=>'alphabetical', 'per_page'=>999 ) );
//print_r($competitionGroups);

global $post;
    $post_slug = $post->post_name;
    print_r($post_slug);

    /* Is the user in any teams */
    //$user_id =get_current_user_id();
    $args = array(
    'group_type' => array( 'competition' ),
    'show_hidden'=> true
    );
    if ( bp_has_groups( $args) ) :
  ?>

    
    <ul>

    <?php while ( bp_groups() ) : bp_the_group(); ?>

      <li>
        <div class="t-m-list">
          <a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( 'type=full' ); ?>
          <h5><?php bp_group_name(); ?></h5>
          </a>
        </div>
      </li>

    <?php endwhile; ?>

    </ul>
    

  <?php else: /* If the user is not in any teams */?>

      <p>No teams</p>

  <?php endif; ?>