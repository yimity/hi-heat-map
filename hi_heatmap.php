<?php
/**
 * @package Hi-Heatmap
 * @version 1.0.0
 */
/*
Plugin Name: Hi Heat Map
Plugin URI: http://wordpress.org/plugins/hi-heatmap/
Description: This is a plugin, for showing the post and page heat map like Github Contribution Graph.
Author: 一米
Version: 1.0.0
Author URI: http://yimity.com/
*/


// 获取热力图数据
function ajax_get_heatmap_callback() {

    // if ( false === ( $result = get_transient( 'heatmap' ) ) ) {
        $calendar = [];

        $after_day = array_key_exists( 'after_day', $_GET ) ? $_GET['after_day'] : 60;

        for ( $i = 0; $i < $after_day; $i ++ ) {
            $day              = date( 'Y-m-d', strtotime( "-$i day" ) );
            $calendar[ $day ] = new stdClass();
        }

        // 获取当前用户最近60天的数据，按时间排序
        $args = [
            'post_type'      => [ 'post', 'page' ],
            'posts_per_page' => '-1',
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'ASC',
            'author__in'     => max( get_current_user_id(), 1 ),
            'date_query'     => [
                'after' => "-$after_day day",
            ],
        ];

        $comments = get_comments( $args ); // 评论

        $posts = get_posts( $args ); // 文章

        foreach ( $comments as $comment ) {
            $date = date( 'Y-m-d', strtotime( $comment->comment_date ) );
            if ( array_key_exists( $date, $calendar ) ) {
                if ( ! isset( $calendar[ $date ]->comments ) ) {
                    $calendar[ $date ]->comments = 0;
                }
                $calendar[ $date ]->comments ++;
            }
        }

        foreach ( $posts as $post ) {
            $date = date( 'Y-m-d', strtotime( $post->post_date ) );
            if ( array_key_exists( $date, $calendar ) ) {
                // 判断文章类型
                if ( $post->post_type === 'note' ) {
                    if ( ! isset( $calendar[ $date ]->notes ) ) {
                        $calendar[ $date ]->notes = 0;
                    }
                    $calendar[ $date ]->notes ++;
                } else {
                    if ( ! isset( $calendar[ $date ]->posts ) ) {
                        $calendar[ $date ]->posts = 0;
                    }
                    $calendar[ $date ]->posts ++;
                }
            }
        }

        // 获取文章总数
        $_posts = wp_count_posts( 'post' );
        // 获取笔记总数
        $_pages = wp_count_posts( 'page' );
        // 最老一篇文章和笔记
        $last = get_posts( [
            'post_type'      => [ 'post', 'page' ],
            'posts_per_rows' => 1,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ] );
        $days = 0;
        if ( $last && count( $last ) ) {
            $last_date = $last[0]->post_date;
            // 判断两个日期相差的天数
            $days = ceil( ( strtotime( date( 'Y-m-d' ) ) - strtotime( $last_date ) ) / 86400 );
        }

        $result = [
            'pages'    => $_pages->publish,
            'posts'    => $_posts->publish,
            'days'     => (string) max( $days, 1 ),
            'calendar' => $calendar,
        ];

        set_transient( 'heatmap', $result, DAY_IN_SECONDS );
    // };
    wp_send_json_success( $result );
}

add_action( 'wp_ajax_get_heatmap', 'ajax_get_heatmap_callback' );
add_action( 'wp_ajax_nopriv_get_heatmap', 'ajax_get_heatmap_callback' );

// We need some JavaScript to FE.
function hi_heatmap_script() {

    wp_enqueue_script( 'hi_heatmap', 
    plugin_dir_url( __FILE__ ) . 'hi_heatmap.js', 
           array ( 'jquery' ), 
           false,
           true
    );
    
        wp_localize_script( 'hi_heatmap', 'hi_heatmap_object',
              array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
  
  }
  add_action( 'wp_enqueue_scripts', 'hi_heatmap_script' );

// We need some CSS to FE.
function hi_heatmap_style() {
    wp_enqueue_style( 'hi_heatmap', plugin_dir_url( __FILE__ ) . 'hi_heatmap.css' );
}
  add_action( 'wp_enqueue_scripts', 'hi_heatmap_style' );

