<?php

/*
*  ACF Site Relationship Field Class
*
*  All the logic for this field type
*
*  @class       acf_field_site_relationship
*  @extends     acf_field
*  @package     ACF
*  @subpackage  Fields
*/

if( ! class_exists('acf_field_site_relationship') ) :

class acf_field_site_relationship extends acf_field {

    /*
    *  __construct
    *
    *  This function will setup the field type data
    *
    *  @type    function
    *  @date    5/03/2014
    *  @since   5.0.0
    *
    *  @param   n/a
    *  @return  n/a
    */

    function __construct() {

        // vars
        $this->name = 'site_relationship';
        $this->label = __("Site",'acf');
        $this->category = 'relational';
        $this->defaults = array(
            'min'               => 0,
            'max'               => 0,
            'return_format'     => 'object'
        );
        $this->l10n = array(
            'min'       => __("Minimum values reached ( {min} values )",'acf'),
            'max'       => __("Maximum values reached ( {max} values )",'acf'),
            'loading'   => __('Loading','acf'),
            'empty'     => __('No matches found','acf'),
        );

        // extra
        add_action('wp_ajax_acf/fields/site_relationship/query',         array($this, 'ajax_query'));
        add_action('wp_ajax_nopriv_acf/fields/site_relationship/query',  array($this, 'ajax_query'));

        // do not delete!
        parent::__construct();

    }


    /*
    *  get_choices
    *
    *  This function will return an array of data formatted for use in a select2 AJAX response
    *
    *  @type    function
    *  @date    15/10/2014
    *  @since   5.0.9
    *
    *  @param   $options (array)
    *  @return  (array)
    */

    function get_choices( $options = array() ) {

        // defaults
        global $wpdb;
        $options = acf_parse_args($options, array(
            'network_id' => $wpdb->siteid,
            'public'     => null,
            'archived'   => null,
            'mature'     => null,
            'spam'       => null,
            'deleted'    => null,
            'limit'      => 10000,
            'offset'     => 0,
            's'                 => '',
            'field_key'         => '',
            'paged'             => 1,
        ));

        // vars
        $args = array();

        // paged
        // $args[ 'sites_per_page' ] = 5;
        // $args[ 'paged' ] = $options[ 'paged' ];

        // load field
        $field = acf_get_field( $options['field_key'] );

        if( !$field ) {
            return false;
        }

        // filters
        $args = apply_filters('acf/fields/site_relationship/query', $args, $field, $options['site_id']);
        $args = apply_filters('acf/fields/site_relationship/query/name=' . $field['name'], $args, $field, $options['site_id'] );
        $args = apply_filters('acf/fields/site_relationship/query/key=' . $field['key'], $args, $field, $options['site_id'] );

        // return
        $r = wp_get_sites($args);

        if ( ! $r || ! is_array( $r ) || empty( $r ) ) {
            return false;
        }

        // Add path to domain (hotfix)
        for ( $i = 0; $i < count( $r ); ++$i ) {
            $r[ $i ][ 'domain' ] = $this->get_site_title( $r[ $i ] );
        }

        // search
        if( $options['s'] ) {
            $temp = $r;
            $r = array();
            foreach ( $temp as $site ) {
                if ( strpos( $site[ 'domain' ], $options[ 's' ] ) > -1 ) {
                    $r[] = $site;
                }
            }
        }

        return $r;

    }

    /*
    *  get_sites_by_blog_id
    *
    *  Return sites by an array of ids
    *
    *  @type    function
    *  @date    24/10/13
    *  @since   5.0.0
    *
    *  @param   $blog_ids (array)
    *  @return  $sites (array)
    */
    function get_sites_by_blog_id( $blog_ids = array() )
    {

        $sites = array();

        if ( is_array( $blog_ids ) && ! empty( $blog_ids ) ) {

            // Cast IDs to integers
            $blog_ids = array_map( 'intval', $blog_ids );

            // Get sites (ouch)
            global $wpdb;
            $temp = wp_get_sites(array(
                'network_id' => $wpdb->siteid,
            ));

            if ( is_array( $temp ) && ! empty( $temp ) ) {
                foreach ( $blog_ids as $blog_id ) {
                    foreach ( $temp as $site ) {
                        if ( $site[ 'blog_id' ] == $blog_id ) {
                            $sites[] = $site;
                            break;
                        }
                    }
                }
            }

        }

        return ! empty( $sites ) ? $sites : false;

    }

    /*
    *  ajax_query
    *
    *  description
    *
    *  @type    function
    *  @date    24/10/13
    *  @since   5.0.0
    *
    *  @param   $site_id (int)
    *  @return  $site_id (int)
    */

    function ajax_query() {

        // validate
        if( empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acf_nonce') ) {
            die();
        }

        // get posts
        $sites = $this->get_choices( $_POST );

        // validate
        if( !$sites ) {
            die();
        }

        // return JSON
        echo json_encode( $sites );
        die();

    }


    /*
    *  get_site_title
    *
    *  This function returns the HTML for a result
    *
    *  @type    function
    *  @date    1/11/2013
    *  @since   5.0.0
    *
    *  @param   $post (object)
    *  @param   $field (array)
    *  @param   $site_id (int) the site_id to which this value is saved to
    *  @return  (string)
    */

    function get_site_title( $site, $field = false, $site_id = 0 ) {

        $title = rtrim( $site[ 'domain' ], '/' ) . ( $site[ 'path' ] != '/' ? '/' . ltrim( $site[ 'path' ], '/' ) : '' );

        // filters
        if ( $field ) {
            $title = apply_filters('acf/fields/site_relationship/result', $title, $site, $field, $site_id);
            $title = apply_filters('acf/fields/site_relationship/result/name=' . $field['_name'], $title, $site, $field, $site_id);
            $title = apply_filters('acf/fields/site_relationship/result/key=' . $field['key'], $title, $site, $field, $site_id);
        }

        // return
        return $title;

    }


    /*
    *  render_field()
    *
    *  Create the HTML interface for your field
    *
    *  @param   $field - an array holding all the field's data
    *
    *  @type    action
    *  @since   3.6
    *  @date    23/01/13
    */

    function render_field( $field ) {

        // vars
        $values = array();
        $atts = array(
            'id'                => $field['id'],
            'class'             => "acf-relationship {$field['class']}",
            'data-min'          => $field['min'],
            'data-max'          => $field['max'],
            'data-s'            => '',
            'data-paged'        => 1,
        );

        // width for select filters
        $width = array(
            'search'    => 0,
        );

        if( !empty($field['filters']) ) {
            $width = array(
                'search'    => 100,
            );
        }

        ?>
<div <?php acf_esc_attr_e($atts); ?>>

    <div class="acf-hidden">
        <input type="hidden" name="<?php echo $field['name']; ?>" value="" />
    </div>

    <?php if( $width['search'] > 0 ): ?>
    <div class="filters">
        <ul class="acf-hl">
            <li style="width:<?php echo $width['search']; ?>%;">
                <div class="inner">
                <input class="filter" data-filter="s" placeholder="<?php _e("Search...",'acf'); ?>" type="text" />
                </div>
            </li>
        </ul>

    </div>
    <?php endif; ?>

    <div class="selection acf-cf">

        <div class="choices">

            <ul class="acf-bl list"></ul>

        </div>

        <div class="values">

            <ul class="acf-bl list">

                <?php if ( ! empty( $field[ 'value' ] ) ) {

                    $sites = $this->get_sites_by_blog_id( $field[ 'value' ] );

                    // set choices
                    if( is_array( $sites ) && ! empty( $sites ) ) {

                        foreach( $sites as $site ) {

                            ?><li>
                                <input type="hidden" name="<?php echo $field[ 'name' ]; ?>[]" value="<?php echo $site[ 'blog_id' ]; ?>" />
                                <span data-id="<?php echo $site[ 'blog_id' ]; ?>" class="acf-rel-item">
                                    <?php echo $this->get_site_title( $site, $field ); ?>
                                    <a href="#" class="acf-icon small dark" data-name="remove_item"><i class="acf-sprite-remove"></i></a>
                                </span>
                            </li><?php

                        }

                    }

                } ?>

            </ul>

        </div>

    </div>

</div>
        <?php
    }



    /*
    *  render_field_settings()
    *
    *  Create extra options for your field. This is rendered when editing a field.
    *  The value of $field['name'] can be used (like bellow) to save extra data to the $field
    *
    *  @type    action
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $field  - an array holding all the field's data
    */

    function render_field_settings( $field ) {

        // vars
        $field['min'] = empty($field['min']) ? '' : $field['min'];
        $field['max'] = empty($field['max']) ? '' : $field['max'];


        // filters
        acf_render_field_setting( $field, array(
            'label'         => __('Filters','acf'),
            'instructions'  => '',
            'type'          => 'checkbox',
            'name'          => 'filters',
            'choices'       => array(
                'search'        => __("Search",'acf'),
            ),
        ));

        // min
        acf_render_field_setting( $field, array(
            'label'         => __('Minimum sites','acf'),
            'instructions'  => '',
            'type'          => 'number',
            'name'          => 'min',
        ));

        // max
        acf_render_field_setting( $field, array(
            'label'         => __('Maximum sites','acf'),
            'instructions'  => '',
            'type'          => 'number',
            'name'          => 'max',
        ));

        // return_format
        acf_render_field_setting( $field, array(
            'label'         => __('Return Format','acf'),
            'instructions'  => '',
            'type'          => 'radio',
            'name'          => 'return_format',
            'choices'       => array(
                'object'        => __("Site Object",'acf'),
                'id'            => __("Site ID",'acf'),
            ),
            'layout'    =>  'horizontal',
        ));

    }


    /*
    *  format_value()
    *
    *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
    *
    *  @type    filter
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $value (mixed) the value which was loaded from the database
    *  @param   $site_id (mixed) the $site_id from which the value was loaded
    *  @param   $field (array) the field array holding all the field options
    *
    *  @return  $value (mixed) the modified value
    */

    function format_value( $value, $site_id, $field ) {

        // bail early if no value
        if( empty($value) ) {
            return $value;
        }

        // convert to int
        $value = array_map( 'intval', $value );

        // load sites if needed
        if( $field['return_format'] == 'object' ) {

            $temp = $this->get_sites_by_blog_id( $value );
            $value = array();

            foreach ( $temp as $site ) {
                // Build an object, include blog details (name etc)
                $site = json_decode( json_encode( array_merge( $site, (array) get_blog_details( $site[ 'blog_id' ] ) ) ), FALSE );
                $site->site_id = (int) $site->site_id;
                $site->blog_id = (int) $site->blog_id;
                $value[] = $site;
            }

        }

        // return
        return $value;

    }


    /*
    *  update_value()
    *
    *  This filter is appied to the $value before it is updated in the db
    *
    *  @type    filter
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   $value - the value which will be saved in the database
    *  @param   $site_id - the $site_id of which the value will be saved
    *  @param   $field - the field array holding all the field options
    *
    *  @return  $value - the modified value
    */

    function update_value( $value, $site_id, $field ) {

        // validate
        if( empty($value) ) {
            return $value;
        }

        // save value as strings, so we can clearly search for them in SQL LIKE statements
        $value = array_map( 'strval', $value );

        // return
        return $value;

    }

    /*
    *  input_admin_enqueue_scripts()
    *
    *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
    *  Use this action to add CSS + JavaScript to assist your render_field() action.
    *
    *  @type    action (admin_enqueue_scripts)
    *  @since   3.6
    *  @date    23/01/13
    *
    *  @param   n/a
    *  @return  n/a
    */

    function input_admin_enqueue_scripts() {

        $dir = plugin_dir_url( __FILE__ );

        // register & include JS
        wp_register_script( 'acf-input-site_relationship', "{$dir}js/input.js" );
        wp_enqueue_script('acf-input-site_relationship');

        // register & include CSS
        wp_register_style( 'acf-input-site_relationship', "{$dir}css/input.css" );
        wp_enqueue_style('acf-input-site_relationship');

    }

}

new acf_field_site_relationship();

endif;

?>
