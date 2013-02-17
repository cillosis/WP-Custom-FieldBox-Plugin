<?php
/*
Plugin Name: Custom Fields Box
Plugin URI: http://www.jeremyharris.me
Description: Widget to render custom fields of post as table of values.
Version: 0.1
Author: Jeremy Harris
Author URI: http://www.jeremyharris.me
License: MIT http://opensource.org/licenses/MIT
*/

// Define Hooks
add_action( 'widgets_init', create_function('', 'return register_widget("jrh_custom_fieldbox_widget");') );

/**
* Widget to display custom fields in an HTML table for POSTS which can by styled by 
* modifiying the /css/theme.css file. 
*/
class jrh_custom_fieldbox_widget extends WP_Widget
{
    /**
    * Widget Initialization
    *
    * This method is called automatically upon registering the widget
    */
    public function jrh_custom_fieldbox_widget()
    {
        $widget_options = array(
            'class_name' => 'jrh_custom_fieldbox_widget',
            'description' => 'Show table of custom fields defined in post.'
        );
        $this->WP_Widget('jrh_custom_fieldbox_widget', 'Post Custom Fields Box');
    }

    /**
    * Widget Form
    *
    * This method is called automatically when rendering the form in the Widget management area.
    *
    * @param Array  Widget instance
    */
    function form($instance)
    {
        $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'ignore' => '' ) );
        $title = isset($instance['title']) ? $instance['title'] : "";
        $ignore = isset($instance['ignore']) ? $instance['ignore'] : "";
        ?>
            Title:<br>
            <input 
                name = "<?php echo($this->get_field_name('title')); ?>"
                type = "text"
                value = "<?php echo(esc_attr($title)); ?>"
            ><br><br>
            Ignore Fields:<br>
            <input 
                name = "<?php echo($this->get_field_name('ignore')); ?>"
                type = "text"
                value = "<?php echo(esc_attr($ignore)); ?>"
            ><br>
            <span style="font-size: 10px;">
                Comma separated list of fields to ignore. Wildcards are accepted such as <b>myfield_*</b>.
            </span>
        <?
    }
 
    /**
    * Widget Form Update
    *
    * This method is called automatically when saving form for widget instance.
    *
    * @param Array  Changed data in widget instance
    * @param Array  Previous data in widget instance
    * @return Array New values merged into instance
    */
    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];
        $instance['ignore'] = $new_instance['ignore'];
        return $instance;
    }

    /**
    * Widget Action
    *
    * This method is called automatically when rendering the widget. This is where we construct the table of 
    * custom fields.
    *
    * @param Array  Arguments describing widget placement
    * @param Array  Instance of form arguments
    */
    function widget($args, $instance)
    {
        // Set Theme
        wp_register_style( 'jrh_custom_fieldbox_theme', plugins_url('css/theme.css', __FILE__) );
        wp_enqueue_style( 'jrh_custom_fieldbox_theme' );

        // Get global post object and verify
        global $post;
        if (is_object($post))
        {
            if(isset($post->ID) && is_numeric($post->ID) && $post->ID > 0)
            {
                if(!is_admin())
                {
                    if(is_single())
                    {
                        // Get arguments and output title
                        extract($args, EXTR_SKIP);
                        echo $before_widget;
                        $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
                        $ignore = empty($instance['ignore']) ? '' :  $instance['ignore'];
                        if (!empty($title))
                            echo $before_title . $title . $after_title;

                        // Get custom fields for post
                        $fields = get_post_custom($post->ID);
                        if(is_array($fields) && count($fields) > 0)
                        {
                            // Initialize output table
                            $output = "\n<div class='custom_fieldbox'><table>";

                            // Cleanup ignore fields
                            $ignore = explode(",", $ignore);
                            if(is_array($ignore))
                            {
                                foreach($ignore as $key => $field)
                                {
                                    // Is this regular expression matching
                                    if(stristr($field, "*"))
                                    {
                                        $ignore[$key] = array(
                                            'type'  => 'regex',
                                            'field' => str_replace("*", "(.)*", trim($field))
                                        );
                                    } 
                                    // Or plain text matching
                                    else 
                                    {
                                        $ignore[$key] = array( 
                                            'type'  => 'text',
                                            'field' => trim($field)
                                        );
                                    }
                                }
                            }

                            // Add rows
                            foreach($fields as $name => $value)
                            {
                                // If we have ignore fields
                                $skipThis = false;
                                if(is_array($ignore) && count($ignore) > 0)
                                {
                                    // See if this field matches any ignores
                                    foreach($ignore as $ignoreField)
                                    {
                                        if($ignoreField['type'] == 'regex')
                                        {
                                            $pattern = '/'.$ignoreField['field'].'/i';
                                            if(preg_match($pattern, $name))
                                            {
                                                $skipThis = true;
                                                continue;
                                            }
                                        } else {
                                            if(stristr($name, $ignoreField['field']))
                                            {
                                                $skipThis = true;
                                                continue;
                                            }
                                        }
                                        
                                    }
                                }
                                if($skipThis == true) 
                                    continue;

                                $output .= "<tr>";
                                $output .= "<td class='custom_fieldbox_title'>$name</td>";
                                $output .= "</tr><tr>";
                                $output .= "<td class='custom_fieldbox_value'>".implode(", ", $value)."</td>";
                                $output .= "</tr>";
                            }

                            // Complete output
                            $output .= "</table></div>\n";

                            echo $output;
                        }

                        echo $after_widget;
                    }
                }
            }
        }
    }
 
}
?>