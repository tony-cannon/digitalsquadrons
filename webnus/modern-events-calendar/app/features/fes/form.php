<?php
/** no direct access **/
defined('MECEXEC') or die();

// Generating javascript code of countdown module
$javascript = '<script type="text/javascript">
jQuery(document).ready(function()
{
    var mec_fes_form_ajax = false;
    jQuery("#mec_fes_form").on("submit", function(event)
    {
        event.preventDefault();
        
        // Hide the message
        jQuery("#mec_fes_form_message").removeClass("mec-success").removeClass("mec-success").html("").hide();

        // Add loading Class to the form
        jQuery("#mec_fes_form").addClass("mec-fes-loading");
        jQuery(".mec-fes-form-cntt").hide();
        jQuery(".mec-fes-form-sdbr").hide();
        jQuery(".mec-fes-submit-wide").hide();

        
        // Fix WordPress editor issue
        jQuery("#mec_fes_content-html").click();
        jQuery("#mec_fes_content-tmce").click();
        
        // Abort previous request
        if(mec_fes_form_ajax) mec_fes_form_ajax.abort();
        
        var data = jQuery("#mec_fes_form").serialize();
        mec_fes_form_ajax = jQuery.ajax(
        {
            type: "POST",
            url: "'.admin_url('admin-ajax.php', NULL).'",
            data: data,
            dataType: "JSON",
            success: function(response)
            {
                // Remove the loading Class from the form
                jQuery("#mec_fes_form").removeClass("mec-fes-loading");
                jQuery(".mec-fes-form-cntt").show();
                jQuery(".mec-fes-form-sdbr").show();
                jQuery(".mec-fes-submit-wide").show();
                
                if(response.success == "1")
                {
                    // Show the message
                    jQuery("#mec_fes_form_message").removeClass("mec-success").addClass("mec-success").html(response.message).css("display","inline-block");
                    
                    // Set the event id
                    jQuery(".mec-fes-post-id").val(response.data.post_id);

                    // Redirect Currnet Page
                    if(response.data.redirect_to != "")
                    {
                        setTimeout(function()
                        {
                            window.location.href = response.data.redirect_to;
                        },' . ((isset($this->settings['fes_thankyou_page_time']) and trim($this->settings['fes_thankyou_page_time']) != '') ? (int) $this->settings['fes_thankyou_page_time'] : 2000) . ');
                    }

                    jQuery(".mec-fes-sub-button").html("Update Event").html("Update Event");
                }
                else
                {
                    // Show the message
                    jQuery("#mec_fes_form_message").removeClass("mec-error").addClass("mec-error").html(response.message).css("display","inline-block");
                }
            },
            error: function(jqXHR, textStatus, errorThrown)
            {
                // Remove the loading Class from the form
                jQuery("#mec_fes_form").removeClass("loading");
            }
        });
    });
});

function mec_fes_upload_featured_image()
{
    var fd = new FormData();
    fd.append("action", "mec_fes_upload_featured_image");
    fd.append("_wpnonce", "'.wp_create_nonce('mec_fes_upload_featured_image').'");
    fd.append("file", jQuery("#mec_featured_image_file").prop("files")[0]);
    
    jQuery("#mec_fes_thumbnail_error").html("").addClass("mec-util-hidden");
    
    jQuery.ajax(
    {
        url: "'.admin_url('admin-ajax.php', NULL).'",
        type: "POST",
        data: fd,
        dataType: "json",
        processData: false,
        contentType: false
    })
    .done(function(data)
    {
        if(data.success)
        {
            jQuery("#mec_fes_thumbnail").val(data.data.url);
            jQuery("#mec_featured_image_file").val("");
            jQuery("#mec_fes_thumbnail_img").html("<img src=\""+data.data.url+"\" />");
            jQuery("#mec_fes_remove_image_button").removeClass("mec-util-hidden");
        }
        else
        {
            jQuery("#mec_fes_thumbnail_error").html(data.message).removeClass("mec-util-hidden");
        }
    });
    
    return false;
}

function mec_fes_upload_location_thumbnail()
{
    var fd = new FormData();
    
    fd.append("action", "mec_fes_upload_featured_image");
    fd.append("_wpnonce", "'.wp_create_nonce('mec_fes_upload_featured_image').'");
    fd.append("file", jQuery("#mec_fes_location_thumbnail_file").prop("files")[0]);
    
    jQuery.ajax(
    {
        url: "'.admin_url('admin-ajax.php', NULL).'",
        type: "POST",
        data: fd,
        dataType: "json",
        processData: false,
        contentType: false
    })
    .done(function(data)
    {
        jQuery("#mec_fes_location_thumbnail").val(data.data.url);
        jQuery("#mec_fes_location_thumbnail_file").val("");
        jQuery("#mec_fes_location_thumbnail_img").html("<img src=\""+data.data.url+"\" />");
        jQuery("#mec_fes_location_remove_image_button").removeClass("mec-util-hidden");
    });
    
    return false;
}

function mec_fes_upload_organizer_thumbnail()
{
    var fd = new FormData();
    
    fd.append("action", "mec_fes_upload_featured_image");
    fd.append("_wpnonce", "'.wp_create_nonce('mec_fes_upload_featured_image').'");
    fd.append("file", jQuery("#mec_fes_organizer_thumbnail_file").prop("files")[0]);
    
    jQuery.ajax(
    {
        url: "'.admin_url('admin-ajax.php', NULL).'",
        type: "POST",
        data: fd,
        dataType: "json",
        processData: false,
        contentType: false
    })
    .done(function(data)
    {
        jQuery("#mec_fes_organizer_thumbnail").val(data.data.url);
        jQuery("#mec_fes_organizer_thumbnail_file").val("");
        jQuery("#mec_fes_organizer_thumbnail_img").html("<img src=\""+data.data.url+"\" />");
        jQuery("#mec_fes_organizer_remove_image_button").removeClass("mec-util-hidden");
    });
    
    return false;
}
</script>';

// Include javascript code into the footer
$this->factory->params('footer', $javascript);
?>
<div class="mec-fes-form">
    <div class="mec-util-hidden" id="mec_fes_form_message"></div>
    <form id="mec_fes_form" enctype="multipart/form-data">
        <?php
            $allday = get_post_meta($post_id, 'mec_allday', true);
            $one_occurrence = get_post_meta($post_id, 'one_occurrence', true);
            $comment = get_post_meta($post_id, 'mec_comment', true);
            $hide_time = get_post_meta($post_id, 'mec_hide_time', true);
            $hide_end_time = get_post_meta($post_id, 'mec_hide_end_time', true);
        
            $start_date = get_post_meta($post_id, 'mec_start_date', true);

            // Advanced Repeating Day
		    $advanced_days = get_post_meta( $post->ID, 'mec_advanced_days', true );
		    $advanced_days = (is_array($advanced_days)) ? $advanced_days : array();
		    $advanced_str = (count($advanced_days)) ? implode('-', $advanced_days) : '';

            $start_time_hour = get_post_meta($post_id, 'mec_start_time_hour', true);
            if(trim($start_time_hour) == '') $start_time_hour = 8;

            $start_time_minutes = get_post_meta($post_id, 'mec_start_time_minutes', true);
            if(trim($start_time_minutes) == '') $start_time_minutes = 0;

            $start_time_ampm = get_post_meta($post_id, 'mec_start_time_ampm', true);
            if(trim($start_time_ampm) == '') $start_time_ampm = 'AM';

            $end_date = get_post_meta($post_id, 'mec_end_date', true);

            $end_time_hour = get_post_meta($post_id, 'mec_end_time_hour', true);
            if(trim($end_time_hour) == '') $end_time_hour = 6;

            $end_time_minutes = get_post_meta($post_id, 'mec_end_time_minutes', true);
            if(trim($end_time_minutes) == '') $end_time_minutes = 0;

            $end_time_ampm = get_post_meta($post_id, 'mec_end_time_ampm', true);
            if(trim($end_time_ampm) == '') $end_time_ampm = 'PM';

            $repeat_status = get_post_meta($post_id, 'mec_repeat_status', true);
            $repeat_type = get_post_meta($post_id, 'mec_repeat_type', true);
            if(trim($repeat_type) == '') $repeat_type = 'daily';

            $repeat_interval = get_post_meta($post_id, 'mec_repeat_interval', true);
            if(trim($repeat_interval) == '' and in_array($repeat_type, array('daily', 'weekly'))) $repeat_interval = 1;

            $certain_weekdays = get_post_meta($post_id, 'mec_certain_weekdays', true);
            if($repeat_type != 'certain_weekdays') $certain_weekdays = array();
            
            $in_days_str = get_post_meta($post_id, 'mec_in_days', true);
            $in_days = trim($in_days_str) ? explode(',', $in_days_str) : array();
            
            $mec_repeat_end = get_post_meta($post_id, 'mec_repeat_end', true);
            if(trim($mec_repeat_end) == '') $mec_repeat_end = 'never';

            $repeat_end_at_occurrences = get_post_meta($post_id, 'mec_repeat_end_at_occurrences', true);
            if(trim($repeat_end_at_occurrences) == '') $repeat_end_at_occurrences = 9;

            $repeat_end_at_date = get_post_meta($post_id, 'mec_repeat_end_at_date', true);

            // This date format used for datepicker
            $datepicker_format = (isset($this->settings['datepicker_format']) and trim($this->settings['datepicker_format'])) ? $this->settings['datepicker_format'] : 'Y-m-d';
            $imported_from_google = get_post_meta($post_id, 'mec_imported_from_google', true);

            $event_timezone = get_post_meta($post->ID, 'mec_timezone', true);
            if(trim($event_timezone) == '') $event_timezone = 'global';

            $countdown_method = get_post_meta($post->ID, 'mec_countdown_method', true);
            if(trim($countdown_method) == '') $countdown_method = 'global';

            // Public Event
            $public = get_post_meta($post->ID, 'mec_public', true);
            if(trim($public) === '') $public = 1;

            //change Submit text depending on new event or an edit
            $formSubmitText = $post_id > 0 ? 'Update Event' : 'Submit Event';
        ?>

        <div class="mec-fes-form-cntt">
            <div class="mec-form-row">
                <label for="mec_fes_title"><?php _e('Title', 'mec'); ?> <span class="mec-required">*</span></label>
                <input type="text" name="mec[title]" id="mec_fes_title" value="<?php echo (isset($post->post_title) ? $post->post_title : ''); ?>" required="required" />
            </div>
            <div class="mec-form-row">
                <?php wp_editor((isset($post->post_content) ? $post->post_content : ''), 'mec_fes_content', array('textarea_name'=>'mec[content]')); ?>
            </div>
            <?php if(isset($this->settings['fes_section_excerpt']) && $this->settings['fes_section_excerpt']): ?>
            <div class="mec-meta-box-fields" id="mec-excerpt">
                <h4><?php _e('Excerpt', 'mec'); ?> <?php echo ((isset($this->settings['fes_required_excerpt']) and $this->settings['fes_required_excerpt']) ? '<span class="mec-required">*</span>' : ''); ?></h4>
                <div class="mec-form-row">
                    <div class="mec-col-12">
                        <textarea name="mec[excerpt]" id="mec_fes_excerpt" class="widefat" rows="10" title="<?php esc_attr_e('Optional Event Excerpt', 'mec'); ?>" placeholder="<?php esc_attr_e('Optional Event Excerpt', 'mec'); ?>" <?php echo ((isset($this->settings['fes_required_excerpt']) and $this->settings['fes_required_excerpt']) ? 'required' : ''); ?>><?php echo (isset($post->post_excerpt) ? $post->post_excerpt : ''); ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php do_action('ds_mec_fes_metabox_event_type', $post); ?>

            <?php if($imported_from_google): ?>
            <p class="info-msg"><?php esc_html_e("This event is imported from Google calendar so if you modify it would overwrite in the next import from Google.", 'mec'); ?></p>
            <?php endif; ?>

            <div class="mec-meta-box-fields" id="mec-date-time">
                <h4><?php _e('Date and Time', 'mec'); ?></h4>
                <div id="mec_meta_box_date_form">
                    <div class="mec-title">
                        <span class="mec-dashicons dashicons dashicons-calendar-alt"></span>
                        <label for="mec_start_date"><?php _e('Start Date', 'mec'); ?></label>
                    </div>
                    <div class="mec-form-row">
                        <div class="mec-col-4">
                            <input type="text" name="mec[date][start][date]" id="mec_start_date" value="<?php echo esc_attr($this->main->standardize_format($start_date, $datepicker_format)); ?>" placeholder="<?php _e('Start Date', 'mec'); ?>" autocomplete="off" />
                        </div>
                        <div class="mec-col-6 mec-time-picker <?php echo ($allday == 1) ? 'mec-util-hidden' : ''; ?>">
                            <?php $this->main->timepicker(array(
                                'method' => (isset($this->settings['time_format']) ? $this->settings['time_format'] : 12),
                                'time_hour' => $start_time_hour,
                                'time_minutes' => $start_time_minutes,
                                'time_ampm' => $start_time_ampm,
                                'name' => 'mec[date][start]',
                                'id_key' => 'start_',
                            )); ?>
                        </div>
                    </div>
                    <div class="mec-title">
                        <span class="mec-dashicons dashicons dashicons-calendar-alt"></span>
                        <label for="mec_end_date"><?php _e('End Date', 'mec'); ?></label>
                    </div>
                    <div class="mec-form-row">
                        <div class="mec-col-4">
                            <input type="text" name="mec[date][end][date]" id="mec_end_date" value="<?php echo esc_attr($this->main->standardize_format($end_date, $datepicker_format)); ?>" placeholder="<?php _e('End Date', 'mec'); ?>" autocomplete="off" />
                        </div>
                        <div class="mec-col-6 mec-time-picker <?php echo ($allday == 1) ? 'mec-util-hidden' : ''; ?>">
                            <?php $this->main->timepicker(array(
                                'method' => (isset($this->settings['time_format']) ? $this->settings['time_format'] : 12),
                                'time_hour' => $end_time_hour,
                                'time_minutes' => $end_time_minutes,
                                'time_ampm' => $end_time_ampm,
                                'name' => 'mec[date][end]',
                                'id_key' => 'end_',
                            )); ?>
                        </div>
                    </div>
                    <div class="mec-form-row">
                        <input <?php if($allday == '1') echo 'checked="checked"'; ?> type="checkbox" name="mec[date][allday]" id="mec_allday" value="1" onchange="jQuery('.mec-time-picker').toggle();" /><label for="mec_allday"><?php _e('All-day Event', 'mec'); ?></label>
                    </div>

                    <?php if(isset($this->settings['tz_per_event']) and $this->settings['tz_per_event']): ?>
                    <div class="mec-form-row mec-timezone-event">
                        <div class="mec-title">
                            <label for="mec_event_timezone"><?php esc_html_e('Timezone', 'mec'); ?></label>
                        </div>
                        <div class="mec-form-row">
                            <div class="mec-col-4">
                                <select name="mec[timezone]" id="mec_event_timezone">
                                    <option value="global"><?php esc_html_e('Inherit from global options'); ?></option>
                                    <?php echo $this->main->timezones($event_timezone); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!isset($this->settings['fes_section_shortcode_visibility']) or (isset($this->settings['fes_section_shortcode_visibility']) and $this->settings['fes_section_shortcode_visibility'])): ?>
                    <h4><?php _e('Visibility', 'mec'); ?></h4>
                    <div class="mec-form-row">
                        <div class="mec-col-4">
                            <select name="mec[public]" id="mec_public" title="<?php esc_attr_e('Event Visibility', 'mec'); ?>">
                                <option value="1" <?php if('1' == $public) echo 'selected="selected"'; ?>><?php _e('Show on Shortcodes', 'mec'); ?></option>
                                <option value="0" <?php if('0' == $public) echo 'selected="selected"'; ?>><?php _e('Hide on Shortcodes', 'mec'); ?></option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            
            <?php do_action('mec_fes_metabox_details', $post); ?>
            
            <?php /* Note feature is enabled */ if($this->main->is_note_visible(get_post_status($post_id))): $note = get_post_meta($post_id, 'mec_note', true); ?>
            <div class="mec-meta-box-fields" id="mec-event-note">
                <h4><?php _e('Note to reviewer', 'mec'); ?></h4>
                <div id="mec_meta_box_event_note">
                    <textarea name="mec[note]"><?php echo $note; ?></textarea>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <div class="mec-fes-form-sdbr">
            
            <!-- Guest Email and Name -->
            <?php if(!is_user_logged_in() and isset($this->settings['fes_guest_name_email']) and $this->settings['fes_guest_name_email']): ?>
            <?php
                $guest_email = get_post_meta($post_id, 'fes_guest_email', true);
                $guest_name = get_post_meta($post_id, 'fes_guest_name', true);
            ?>
            <div class="mec-meta-box-fields" id="mec-guest-email-link">
                <h4><?php _e('User Data', 'mec'); ?></h4>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_guest_email"><?php _e('Email', 'mec'); ?><span>*</span></label>
                    <input class="mec-col-7" type="email" required="required" name="mec[fes_guest_email]" id="mec_guest_email" value="<?php echo esc_attr($guest_email); ?>" placeholder="<?php _e('eg. yourname@gmail.com', 'mec'); ?>" />
                </div>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_guest_name"><?php _e('Name', 'mec'); ?><span>*</span></label>
                    <input class="mec-col-7" type="text" required="required" name="mec[fes_guest_name]" id="mec_guest_name" value="<?php echo esc_attr($guest_name); ?>" placeholder="<?php _e('eg. John Smith', 'mec'); ?>" />
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Event Links Section -->
            <?php if(!isset($this->settings['fes_section_event_links']) or (isset($this->settings['fes_section_event_links']) and $this->settings['fes_section_event_links'])): ?>
            <?php
                $read_more = get_post_meta($post_id, 'mec_read_more', true);
                $more_info = get_post_meta($post_id, 'mec_more_info', true);
                $more_info_title = get_post_meta($post_id, 'mec_more_info_title', true);
                $more_info_target = get_post_meta($post_id, 'mec_more_info_target', true);
            ?>
            <div class="mec-meta-box-fields" id="mec-event-links">
                <h4><?php _e('Event Links', 'mec'); ?></h4>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_read_more_link"><?php echo $this->main->m('read_more_link', __('Event Link', 'mec')); ?> <?php echo ((isset($this->settings['fes_required_event_link']) and $this->settings['fes_required_event_link']) ? '<span class="mec-required">*</span>' : ''); ?></label>
                    <input class="mec-col-9" type="text" name="mec[read_more]" id="mec_read_more_link" value="<?php echo esc_attr($read_more); ?>" placeholder="<?php _e('eg. http://yoursite.com/your-event', 'mec'); ?>" <?php echo ((isset($this->settings['fes_required_event_link']) and $this->settings['fes_required_event_link']) ? 'required' : ''); ?> />
                    <p class="description"><?php _e('If you fill it, it will replace the default event page link. Insert full link including http(s)://', 'mec'); ?></p>
                </div>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_more_info_link"><?php echo $this->main->m('more_info_link', __('More Info', 'mec')); ?> <?php echo ((isset($this->settings['fes_required_more_info_link']) and $this->settings['fes_required_more_info_link']) ? '<span class="mec-required">*</span>' : ''); ?></label>
                    <input class="mec-col-5" type="text" name="mec[more_info]" id="mec_more_info_link" value="<?php echo esc_attr($more_info); ?>" placeholder="<?php _e('eg. http://yoursite.com/your-event', 'mec'); ?>" <?php echo ((isset($this->settings['fes_required_more_info_link']) and $this->settings['fes_required_more_info_link']) ? 'required' : ''); ?> />
                    <input class="mec-col-2" type="text" name="mec[more_info_title]" id="mec_more_info_title" value="<?php echo esc_attr($more_info_title); ?>" placeholder="<?php _e('More Information', 'mec'); ?>" />
                    <select class="mec-col-2" name="mec[more_info_target]" id="mec_more_info_target">
                        <option value="_self" <?php echo ($more_info_target == '_self' ? 'selected="selected"' : ''); ?>><?php _e('Current Window', 'mec'); ?></option>
                        <option value="_blank" <?php echo ($more_info_target == '_blank' ? 'selected="selected"' : ''); ?>><?php _e('New Window', 'mec'); ?></option>
                    </select>
                    <p class="description"><?php _e('If you fill it, it will be shown in event details page as an optional link. Insert full link including http(s)://', 'mec'); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Event Cost Section -->
            <?php if(!isset($this->settings['fes_section_cost']) or (isset($this->settings['fes_section_cost']) and $this->settings['fes_section_cost'])): ?>
            <?php
                $cost = get_post_meta($post_id, 'mec_cost', true);
                $cost_type = ((isset($this->settings['single_cost_type']) and trim($this->settings['single_cost_type'])) ? $this->settings['single_cost_type'] : 'numeric');

                $currency = get_post_meta($post_id, 'mec_currency', true);
                if(!is_array($currency)) $currency = array();

                $currency_per_event = ((isset($this->settings['currency_per_event']) and trim($this->settings['currency_per_event'])) ? $this->settings['currency_per_event'] : 0);

                $currencies = $this->main->get_currencies();
                $current_currency = (isset($currency['currency']) ? $currency['currency'] : (isset($this->settings['currency']) ? $this->settings['currency'] : NULL));
            ?>
            <div class="mec-meta-box-fields" id="mec-event-cost">
                <h4><?php echo $this->main->m('event_cost', __('Event Cost', 'mec')); ?> <?php echo ((isset($this->settings['fes_required_cost']) and $this->settings['fes_required_cost']) ? '<span class="mec-required">*</span>' : ''); ?></h4>
                <div id="mec_meta_box_cost_form">
                    <div class="mec-form-row">
                        <input type="<?php echo ($cost_type === 'alphabetic' ? 'text' : 'number'); ?>" <?php echo ($cost_type === 'numeric' ? 'min="0" step="any"' : ''); ?> class="mec-col-3" name="mec[cost]" id="mec_cost" value="<?php echo esc_attr($cost); ?>" placeholder="<?php _e('Cost', 'mec'); ?>" <?php echo ((isset($this->settings['fes_required_cost']) and $this->settings['fes_required_cost']) ? 'required' : ''); ?> />
                    </div>
                </div>

                <?php if($currency_per_event): ?>
                <h4><?php echo __('Currency Options', 'mec'); ?></h4>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_currency_currency"><?php _e('Currency', 'mec'); ?></label>
                    <div class="mec-col-4">
                        <select name="mec[currency][currency]" id="mec_currency_currency">
                            <?php foreach($currencies as $c=>$currency_name): ?>
                                <option value="<?php echo $c; ?>" <?php echo (($current_currency == $c) ? 'selected="selected"' : ''); ?>><?php echo $currency_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_currency_currency_symptom"><?php _e('Currency Sign', 'mec'); ?></label>
                    <div class="mec-col-4">
                        <input type="text" name="mec[currency][currency_symptom]" id="mec_currency_currency_symptom" value="<?php echo (isset($currency['currency_symptom']) ? $currency['currency_symptom'] : ''); ?>" />
                        <span class="mec-tooltip">
                            <div class="box left">
                                <h5 class="title"><?php _e('Currency Sign', 'mec'); ?></h5>
                                <div class="content"><p><?php esc_attr_e("Default value will be \"currency\" if you leave it empty.", 'mec'); ?><a href="https://webnus.net/dox/modern-events-calendar/currency-options/" target="_blank"><?php _e('Read More', 'mec'); ?></a></p></div>
                            </div>
                            <i title="" class="dashicons-before dashicons-editor-help"></i>
                        </span>
                    </div>
                </div>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_currency_currency_sign"><?php _e('Currency Position', 'mec'); ?></label>
                    <div class="mec-col-4">
                        <select name="mec[currency][currency_sign]" id="mec_currency_currency_sign">
                            <option value="before" <?php echo ((isset($currency['currency_sign']) and $currency['currency_sign'] == 'before') ? 'selected="selected"' : ''); ?>><?php _e('$10 (Before)', 'mec'); ?></option>
                            <option value="before_space" <?php echo ((isset($currency['currency_sign']) and $currency['currency_sign'] == 'before_space') ? 'selected="selected"' : ''); ?>><?php _e('$ 10 (Before with Space)', 'mec'); ?></option>
                            <option value="after" <?php echo ((isset($currency['currency_sign']) and $currency['currency_sign'] == 'after') ? 'selected="selected"' : ''); ?>><?php _e('10$ (After)', 'mec'); ?></option>
                            <option value="after_space" <?php echo ((isset($currency['currency_sign']) and $currency['currency_sign'] == 'after_space') ? 'selected="selected"' : ''); ?>><?php _e('10 $ (After with Space)', 'mec'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_currency_thousand_separator"><?php _e('Thousand Separator', 'mec'); ?></label>
                    <div class="mec-col-4">
                        <input type="text" name="mec[currency][thousand_separator]" id="mec_currency_thousand_separator" value="<?php echo (isset($currency['thousand_separator']) ? $currency['thousand_separator'] : ','); ?>" />
                    </div>
                </div>
                <div class="mec-form-row">
                    <label class="mec-col-2" for="mec_currency_decimal_separator"><?php _e('Decimal Separator', 'mec'); ?></label>
                    <div class="mec-col-4">
                        <input type="text" name="mec[currency][decimal_separator]" id="mec_currency_decimal_separator" value="<?php echo (isset($currency['decimal_separator']) ? $currency['decimal_separator'] : '.'); ?>" />
                    </div>
                </div>
                <div class="mec-form-row">
                    <div class="mec-col-12">
                        <label for="mec_currency_decimal_separator_status">
                            <input type="hidden" name="mec[currency][decimal_separator_status]" value="1" />
                            <input type="checkbox" name="mec[currency][decimal_separator_status]" id="mec_currency_decimal_separator_status" <?php echo ((isset($currency['decimal_separator_status']) and $currency['decimal_separator_status'] == '0') ? 'checked="checked"' : ''); ?> value="0" />
                            <?php _e('No decimal', 'mec'); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Event Featured Image Section -->
            <?php if(!isset($this->settings['fes_section_featured_image']) or (isset($this->settings['fes_section_featured_image']) and $this->settings['fes_section_featured_image'])): ?>
            <?php
                $attachment_id = get_post_thumbnail_id($post_id);
                $featured_image = wp_get_attachment_image_src($attachment_id, 'large');
                if(isset($featured_image[0])) $featured_image = $featured_image[0];
            ?>
            <div class="mec-meta-box-fields" id="mec-featured-image">
                <h4><?php _e('Featured Image', 'mec'); ?></h4>
                <div class="mec-form-row">
                    <span id="mec_fes_thumbnail_img"><?php echo (trim($featured_image) ? '<img src="'.$featured_image.'" />' : ''); ?></span>
                    <input type="hidden" id="mec_fes_thumbnail" name="mec[featured_image]" value="<?php if(isset($attachment_id) and intval($attachment_id)) the_guid($attachment_id); ?>" />
                    <input type="file" id="mec_featured_image_file" onchange="mec_fes_upload_featured_image();" />
                    <span id="mec_fes_remove_image_button" class="<?php echo (trim($featured_image) ? '' : 'mec-util-hidden'); ?>"><?php _e('Remove Image', 'mec'); ?></span>

                    <div class="mec-error mec-util-hidden" id="mec_fes_thumbnail_error"></div>
                </div>

                <?php if(isset($this->settings['featured_image_caption']) and $this->settings['featured_image_caption']): ?>
                <div class="mec-form-row">
                    <input type="text" id="mec_fes_thumbnail_caption" name="mec[featured_image_caption]" value="<?php if(isset($attachment_id) and intval($attachment_id)) echo wp_get_attachment_caption($attachment_id); ?>" />
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Event Category Section -->
            <?php if(!isset($this->settings['fes_section_categories']) or (isset($this->settings['fes_section_categories']) and $this->settings['fes_section_categories'])): ?>
            <div class="mec-meta-box-fields" id="mec-categories">
                <h4><?php echo $this->main->m('taxonomy_categories', __('Categories', 'mec')); ?> <?php echo ((isset($this->settings['fes_required_category']) and $this->settings['fes_required_category']) ? '<span class="mec-required">*</span>' : ''); ?></h4>
                <div class="mec-form-row">
                    <?php 
                        wp_list_categories(array(
                            'taxonomy' => 'mec_category',
                            'hide_empty' => false,
                            'title_li' => '',
                            'walker' => new FES_Custom_Walker($post_id),
                        ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Event Label Section -->
            <?php if(!isset($this->settings['fes_section_labels']) or (isset($this->settings['fes_section_labels']) and $this->settings['fes_section_labels'])): ?>
            <?php
                $post_labels = get_the_terms($post_id, 'mec_label');

                $labels = array();
                if($post_labels) foreach($post_labels as $post_label) $labels[] = $post_label->term_id;
                
                $label_terms = get_terms(array('taxonomy'=>'mec_label', 'hide_empty'=>false));
            ?>
            <?php if(count($label_terms)): ?>
            <div class="mec-meta-box-fields" id="mec-labels">
                <h4><?php echo $this->main->m('taxonomy_labels', __('Labels', 'mec')); ?> <?php echo ((isset($this->settings['fes_required_label']) and $this->settings['fes_required_label']) ? '<span class="mec-required">*</span>' : ''); ?></h4>
                <div class="mec-form-row">
                    <?php foreach($label_terms as $label_term): ?>
                    <label for="mec_fes_labels<?php echo $label_term->term_id; ?>">
                        <input type="checkbox" name="mec[labels][<?php echo $label_term->term_id; ?>]" id="mec_fes_labels<?php echo $label_term->term_id; ?>" value="1" <?php echo (in_array($label_term->term_id, $labels) ? 'checked="checked"' : ''); ?> />
                        <?php do_action('mec_label_to_checkbox_frontend', $label_term, $labels ) ?>
                        <?php echo $label_term->name; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <!-- Event Color Section -->
            <?php if(!isset($this->settings['fes_section_event_color']) or (isset($this->settings['fes_section_event_color']) and $this->settings['fes_section_event_color'])): ?>
            <?php
                $color = get_post_meta($post_id, 'mec_color', true);
                $available_colors = $this->main->get_available_colors();

                if(!trim($color)) $color = $available_colors[0];
            ?>
            <?php if(count($available_colors)): ?>
            <div class="mec-meta-box-fields" id="mec-event-color">
                <h4><?php _e('Event Color', 'mec'); ?></h4>
                <div class="mec-form-row">
                    <div class="mec-form-row mec-available-color-row">
                        <input type="hidden" id="mec_event_color" name="mec[color]" value="#<?php echo $color; ?>" />
                        <?php foreach($available_colors as $available_color): ?>
                        <span class="mec-color <?php echo ($available_color == $color ? 'color-selected' : ''); ?>" onclick="mec_set_event_color('<?php echo $available_color; ?>');" style="background-color: #<?php echo $available_color; ?>"></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <!-- Event Tags Section -->
            <?php if(!isset($this->settings['fes_section_tags']) or (isset($this->settings['fes_section_tags']) and $this->settings['fes_section_tags'])): ?>
            <?php
                $post_tags = wp_get_post_terms($post_id, apply_filters('mec_taxonomy_tag', ''));

                $tags = '';
                foreach($post_tags as $post_tag) $tags .= $post_tag->name.',';
            ?>
            <div class="mec-meta-box-fields" id="mec-tags">
                <h4><?php _e('Tags', 'mec'); ?></h4>
                <div class="mec-form-row">
                    <textarea name="mec[tags]" id="mec_fes_tags" placeholder="<?php esc_attr_e('Insert your desired tags, comma separated.', 'mec'); ?>"><?php echo (trim($tags) ? trim($tags, ', ') : ''); ?></textarea>
                </div>
            </div>
            <?php endif; ?>

            <!-- Event Speakers Section -->
            <?php if((isset($this->settings['speakers_status']) and $this->settings['speakers_status']) and isset($this->settings['fes_section_speaker']) and $this->settings['fes_section_speaker']): ?>
                <?php
                $post_speakers = get_the_terms($post_id, 'mec_speaker');

                $speakers = array();
                if($post_speakers) foreach($post_speakers as $post_speaker)
                {
                    if(!isset($post_speaker->term_id)) continue;
                    $speakers[] = $post_speaker->term_id;
                }

                $speaker_terms = get_terms(array('taxonomy'=>'mec_speaker', 'hide_empty'=>false));
                ?>
                    <div class="mec-meta-box-fields" id="mec-speakers">
                        <h4><?php echo $this->main->m('taxonomy_speakers', __('Speakers', 'mec')); ?></h4>
                        <div class="mec-form-row">
                            <input type="text" name="mec[speakers][datas][names]" id="mec_speaker_input_names" placeholder="<?php _e('Speakers Names', 'mec'); ?>" class="" />
                            <p><?php _e('Separate names with commas: Justin, Chris', 'mec'); ?></p>
                            <button class="button" type="button" id="mec_add_speaker_button"><?php _e('Add', 'mec'); ?></button>
                        </div>
                        <div class="mec-form-row" id="mec-fes-speakers-list">
                        <?php if(count($speaker_terms)): ?>
                            <?php foreach($speaker_terms as $speaker_term): ?>
                                <label for="mec_fes_speakers<?php echo $speaker_term->term_id; ?>">
                                    <input type="checkbox" name="mec[speakers][<?php echo $speaker_term->term_id; ?>]" id="mec_fes_speakers<?php echo $speaker_term->term_id; ?>" value="1" <?php echo (in_array($speaker_term->term_id, $speakers) ? 'checked="checked"' : ''); ?> />
                                    <?php echo $speaker_term->name; ?>
                                </label>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
            <?php endif; ?>

            <?php do_action( 'ds_mec_fes_metabox_server_details', $post ); ?>

            <!-- Virtual Section -->
            <?php if(isset($this->settings['fes_section_virtual_events']) && $this->settings['fes_section_virtual_events']):

                if ( $post->ID != -1 && $post == "" ) {
                    $post = get_post_meta($post->ID, 'meta_box_virtual', true);
                }

                do_action('mec_virtual_event_form', $post);

            endif; ?>

            <!-- Zoom Event Section -->
            <?php
            if(isset($this->settings['fes_section_zoom_integration']) && $this->settings['fes_section_zoom_integration'])
            {
                if($post->ID != -1 && $post == "") $post = get_post_meta($post->ID, 'meta_box_virtual', true);
                do_action('mec_zoom_event_form', $post);
            }
            ?>
        </div>
        <div class="mec-form-row mec-fes-submit-wide">

            <!-- Agreement Section -->
            <?php if(isset($this->settings['fes_agreement']) and $this->settings['fes_agreement']): ?>
            <label>
                <input type="hidden" name="mec[agreement]" value="0">
                <input type="checkbox" name="mec[agreement]"  value="1" <?php echo (isset($this->settings['fes_agreement_checked']) and $this->settings['fes_agreement_checked']) ? 'checked="checked"' : ''; ?>>

                <?php if(isset($this->settings['fes_agreement_page']) and $this->settings['fes_agreement_page']): ?>
                <span><?php echo sprintf(esc_html__('I accept the %s in order to submit an event.', 'mec'), '<a href="'.get_permalink($this->settings['fes_agreement_page']).'" target="_blank">'.esc_html__('Privacy Policy', 'mec').'</a>'); ?> <span class="mec-required">*</span></span>
                <?php else: ?>
                <span><?php esc_html_e('I accept the Privacy Policy in order to submit an event.', 'mec'); ?> <span class="mec-required">*</span></span>
                <?php endif; ?>
            </label>
            <?php endif; ?>

            <?php if($this->main->get_recaptcha_status('fes')): ?><div class="mec-form-row mec-google-recaptcha"><div class="g-recaptcha" data-sitekey="<?php echo $this->settings['google_recaptcha_sitekey']; ?>"></div></div><?php endif; ?>
            <button class="mec-fes-sub-button" type="submit"><?php _e( $formSubmitText, 'mec'); ?></button>
            <div class="mec-util-hidden">
                <input type="hidden" name="mec[post_id]" value="<?php echo $post_id; ?>" id="mec_fes_post_id" class="mec-fes-post-id" />
                <input type="hidden" name="action" value="mec_fes_form" />
                <?php wp_nonce_field('mec_fes_form'); ?>
                <?php wp_nonce_field('mec_event_data', 'mec_event_nonce'); ?>
            </div>

        </div>
    </form>
</div>