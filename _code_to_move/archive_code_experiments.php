<?php


function oerbox_get_meta_box_attachments($meta_boxes){

  $prefix = 'oerbox_';

  // https://docs.metabox.io/fields/post/
  // 2DO: we can hide/show the rest with https://docs.metabox.io/extensions/meta-box-conditional-logic/ - this should only be visible in attachments
  $fields_attachment = array(
    array(
      'name'        => 'Seite/Eintrag',
      'id'          => $prefix .'get_metadata_from_page',
      'type'        => 'post',
      'desc' => esc_html__('Wenn diese Option aktiviert ist durch die Auswahl einer Seite/eines Beitrags, dann müssen die Metadaten (Lizenz, Urheber*innen, etc.) nicht mehr manuell für dieses Medienobjekt eingetragen werden. Sie werden automatisch übernommen wenn die Mediendatei aufgerufen wird.','oerbox'),
      // Post type.
      'post_type'   => array('page','post','material'), // 2DO: custom types!!! (see above, we need to automatically get the custom post types by pods)
      // Field type.
      'field_type'  => 'select_advanced',
      // Placeholder, inherited from `select_advanced` field.
      'placeholder' => 'Seite/Beitrag auswählen',
      // Query arguments. See https://codex.wordpress.org/Class_Reference/WP_Query
      'query_args'  => array(
        // we also want to allow drafts
        //'post_status'    => 'publish',
        'posts_per_page' => - 1,
      )),
    );


      $meta_boxes[] = array(
        'id' => 'oerbox-attachment',
        'title' => esc_html__( 'Metadaten von Seite/Beitrag/Eintrag übernehmen?', 'oerbox'),
        // 2DO: "materials" was hardcoded here, we need to solve this dynamically (post type by pods) -> global setting
        'post_types' => array('attachment'),
        'context' => 'advanced',
        'priority' => 'high',
        'autosave' => 'true',
        'fields' => $fields_attachment
      );

      return $meta_boxes;

      }

      add_filter( 'rwmb_meta_boxes', 'oerbox_get_meta_box_attachments' );


              // 2DO: custom link for media (attachment) metadata

              function oerbox_attachment_fields_to_edit( $fields, $post ) {


                // 2DO: check if ID is correct?

                $post_edit_link = "post.php?action=edit&post=".$post->ID."#oerbox";

                $fields['test-media-item'] = array(
                  'label' => 'OERbox',
                  'input' => 'html',
                  'html' => '<a href="'.$post_edit_link.'" target="_blank">OER-Metadaten bearbeiten</a>',
                  'show_in_edit' => false,
                );

                return $fields;
              }
              add_filter( 'attachment_fields_to_edit', 'oerbox_attachment_fields_to_edit', 10, 2 );


              // 2DO: Box at media attachment page?
              // 2DO: sitemap for attachment page

              // hack for file blocks, we need the id attached as class
              // https://github.com/zgordon/advanced-gutenberg-course/blob/master/lib/block-filters.php
              add_filter( 'render_block', 'oerbox_block_filters', 10, 3);
              function oerbox_block_filters( $block_content, $block ) {

                // 2DO: add is_single?

                // if block is core/file, we attach the id for media metadata handling
                if( in_array($block['blockName'], array("core/file","core/audio","core/video")) && isset($block['attrs']['id'])) {
                  $block_abbr = str_replace("core/","",$block['blockName']);
                  $output = '<!-- oerbox workaround --><span style="display:none;" class="wp-'.$block_abbr.'-'.$block['attrs']['id'].'">';
                  $output .= '</span><!-- eo oerbox workaround -->';
                  $output .= $block_content;
                  return $output;
                }

                return $block_content;
              }


              // 2DO: box at the end of wordpress article

              function oerbox_after_content($content) {

                $oerbox_html = "";


                // find all image blocks (e.g. class wp-image-27)
                // only works with gutenberg editor
                $pattern = '/wp-image-(\d{1,12})/';
                preg_match_all($pattern, $content, $matches);
                // $matches[1] > array of the extracted results
                if(count($matches[1])>0){
                  // add image license info to box
                  foreach($matches[1] as $image_ID){
                    // 2DO: add to list
                    //print_r(get_post_meta($image_ID));
                  }
                }
                //print_r($matches); // 3333

                // 2DO: match all media files
                $pattern = '/wp-file-(\d{1,12})/';
                $pattern = '/wp-audio-(\d{1,12})/';
                $pattern = '/wp-video-(\d{1,12})/';


                // 2DO: include it all in schema.org?
                // (automatically append h5p or image subtypes for general URL?)


                // find all file blocks div.wp-block-file
                // 2DO: unfortunately not with id?
                // 2DO: get url and find out ID?
                //https://github.com/WordPress/gutenberg/issues/6356
                // 2 WATCH: https://javascriptforwp.com/extending-wordpress-blocks/


                // find all file blocks

                // this only gets attachments uploaded while editing the post :(
                //$media_attachments = get_attached_media('');
                // more solutions: https://wordpress.stackexchange.com/questions/288416/how-to-get-all-files-inserted-but-not-attached-to-a-post

                // new gutenberg block option?
                //print_r(parse_blocks($content ));

                $media_attachments = get_posts( array(
                  'post_type' => 'attachment',
                  'posts_per_page' => -1,
                  'post_parent' => get_the_ID(),
                  'exclude'     => get_post_thumbnail_id()
                ) );

                if(count($media_attachments) > 0){
                  $oerbox_html .= "Medieninhalte: ";
                  //var_dump($media_attachments);
                  $attachments_debug = "<pre>".print_r($media_attachments,true)."</pre>";
                  $oerbox_html .= $attachments_debug;
                  $oerbox_html .= "<ul>";
                  foreach($media_attachments as $attachment){
                    $oerbox_html .= "<li>TITLE VON AUTHOR/ORGS, LICENSE/URL, QUELLE</li>";
                  }
                  $oerbox_html .= "</ul>";
                }

                $fullcontent = $content.$oerbox_html;
                return $fullcontent;


                /*if(is_page() || is_single()) {
                $beforecontent = 'This goes before the content. Isn\'t that awesome!';
                $aftercontent = 'And this will come after, so that you can remind them of something, like following you on Facebook for instance.';
                $fullcontent = $beforecontent . $content . $aftercontent;
              } else {
              $fullcontent = $content;
            }
            return $fullcontent;*/
          }
          add_filter('the_content', 'oerbox_after_content');

          // gett all attached media, show license information (we don't want to mess with the caption?)


          // Add relationship between oer authors (static directory) and posts (OERs)
          // 2DO: RENAME!
          add_action( 'mb_relationships_init', function () {
              MB_Relationships_API::register( [
                  'id'   => 'posts_to_oerauthors',

                  'from' => [
                      'object_type' => 'post',
                      'post_type'=> 'post',
                      'meta_box'    => [
                          'title' => 'Manages',
                          'context' => 'after_title'
                      ]
                  ],
                  'to'   => [
                      'object_type' => 'post',
                      'post_type'   => 'oer-author',
                      'meta_box'    => [
                          'title' => 'Managed By',
                          'context'=>'after_title',
                      ],
                  ],
              ] );
          } );







          function author_cap_filter( $allcaps, $cap, $args ) {

          	// Bail out if we're not asking about a post:
          	if ( 'edit_post' != $args[0] )
          		return $allcaps;

          	// Bail out for users who can already edit others posts:
          	//if ( $allcaps['edit_others_posts'] )
          		// return $allcaps;

          	// Bail out for users who can't publish posts:
          	/*if ( !isset( $allcaps['publish_posts'] ) or !$allcaps['publish_posts'] )
          		return $allcaps;*/

          	// Load the post data:
          	$post = get_post( $args[2] );

          	// Bail out if the user is the post author:
          	//if ( $args[1] == $post->post_author )
          		//return $allcaps;

          	// Bail out if the post isn't pending or published:
          	//if ( ( 'pending' != $post->post_status ) and ( 'publish' != $post->post_status ) )
          		//return $allcaps;

          	// Load the author data:
            $userID = $args[1];

          	// $author = new WP_User( $post->post_author );

          	// Bail out if post author can edit others posts:
          	//if ( $author->has_cap( 'edit_others_posts' ) )
          		//return $allcaps;

          	$allcaps[$cap[0]] = true;

          	return $allcaps;

          }
          add_filter( 'user_has_cap', 'author_cap_filter', 10, 3 );

          // https://wordpress.stackexchange.com/a/313720
          add_action ('admin_head', 'wpse313020_change_author');
          function wpse313020_change_author () {
            global $pagenow;
            global $post;
            //var_dump($post->ID);
            $current_user = wp_get_current_user();
            // only do this if current user is contributor
            if ('contributor' == $current_user->roles[0]) {
              // add capability when we're editing a post, remove it when we're not

              // 2DO: check if function exists, this is from co authors plus
              // https://wordpress.stackexchange.com/questions/318955/co-authors-plus-how-do-i-get-all-authors-with-a-query
              $current_co_authors_for_post = get_coauthors();
              //var_dump($current_co_authors_for_post);
              $current_user_is_author = false;
              // 2DO: check also $post->author? (is string id) or is it in get_coauthors?
              if(count($current_co_authors_for_post) > 0){
                foreach($current_co_authors_for_post as $author_object){
                  if($author_object->linked_account == $current_user->user_login){
                    $current_user_is_author = true;
                  }
                  break;
                }
              }

              // 2DO: unfortunately this needs a reload so that user can edit this?
              if ('post.php' == $pagenow && $current_user_is_author){
                // give user temporary rights to edit other posts (only this will show the coauthors box in post edit screen)
                // echo "APPROVED";
                $current_user->add_cap('edit_others_posts');
              }
              else{
                if(current_user_can('edit_others_posts') ){
                  // force reload, otherwise security hole
                  $current_user->remove_cap('edit_others_posts');

                  // THIS IS NOT THE USERS POST, A USER TRIED TO ACCESS ANOTHER ID WITH TEMPORARY EDIT RIGHTS
                  if('post.php' == $pagenow && !$current_user_is_author){
                    // after reload the permissions are correct, without reload user is able to access edit screen
                    wp_die("Bitte Seite neu laden", "Please reload");
                  }
                }
               }
              }
          }

          // https://wpsites.net/wordpress-admin/add-top-level-custom-admin-menu-link-in-dashboard-to-any-url/
          add_action( 'admin_menu', 'register_custom_menu_link' );
          /**
           * @author    Brad Dalton
           * @example   http://wpsites.net/wordpress-admin/add-top-level-custom-admin-menu-link-in-dashboard-to-any-url/
           * @copyright 2014 WP Sites
           */
          function register_custom_menu_link(){
              add_menu_page( 'custom menu link', 'OER authors', 'manage_options', 'users.php?page=view-guest-authors', '', 'dashicons-groups', 4 );
              // we don't need the redirect, it works with URL in slug-param?
              //  add_menu_page( 'custom menu link', 'Your Menu Link', 'manage_options', 'any-url', 'wpsites_custom_menu_link', 'dashicons-external', 3 );

          }
          // see above, we don't need the redirect?
          /*function wpsites_custom_menu_link(){
              wp_redirect( 'http://www.example.com', 301 );
            exit;
          }*/

          // //Let Contributor Role to Upload Media and edit their published posts
          add_action ('admin_init', 'allow_contributors_to_upload_media_and_edit_published');
          function allow_contributors_to_upload_media_and_edit_published(){
            $contributor = get_role('contributor');
            $contributor->add_cap('upload_files');
            $contributor->add_cap('edit_published_posts'); // 2DO: option in backend?

            /*$current_user = wp_get_current_user();
            if ( current_user_can('contributor') && !current_user_can('upload_files') ){
            $current_user->add_cap('upload_files');
          }
          if ( current_user_can('contributor') && !current_user_can('edit_published_posts') ){
          $current_user->add_cap('edit_published_posts');
          }*/
          }
