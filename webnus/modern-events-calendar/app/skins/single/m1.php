<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var MEC_skin_single $this */

$booking_options = get_post_meta($event->data->ID, 'mec_booking', true);
if(!is_array($booking_options)) $booking_options = array();

$event_link = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'read_more', (isset($event->data->meta['mec_read_more']) ? $event->data->meta['mec_read_more'] : ''));

$more_info = (isset($event->data->meta['mec_more_info']) and trim($event->data->meta['mec_more_info']) and $event->data->meta['mec_more_info'] != 'http://') ? $event->data->meta['mec_more_info'] : '';
if(isset($event->date) and isset($event->date['start']) and isset($event->date['start']['timestamp'])) $more_info = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'more_info', $more_info);

$more_info_target = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'more_info_target', (isset($event->data->meta['mec_more_info_target']) ? $event->data->meta['mec_more_info_target'] : '_self'));
$more_info_title = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'more_info_title', ((isset($event->data->meta['mec_more_info_title']) and trim($event->data->meta['mec_more_info_title'])) ? $event->data->meta['mec_more_info_title'] : __('Read More', 'mec')));

$location_id = $this->main->get_master_location_id($event);
$location = ($location_id ? $this->main->get_location_data($location_id) : array());

$organizer_id = $this->main->get_master_organizer_id($event);
$organizer = ($organizer_id ? $this->main->get_organizer_data($organizer_id) : array());

$discordEvent = new DS_MEC_Discord_Event();

?>
<div class="mec-wrap <?php echo $event_colorskin; ?> clearfix <?php echo $this->html_class; ?> mec-modal-wrap" id="mec_skin_<?php echo $this->uniqueid; ?>">
    <article class="mec-single-event mec-single-modern mec-single-modal">
        <?php echo $this->main->display_cancellation_reason($event, $this->display_cancellation_reason); ?>
        <h1 class="mec-single-title"><?php echo get_the_title($event->data->ID); ?></h1>
        <div class="mec-single-event-bar">
            <?php
            // Event Date and Time
            if(isset($event->data->meta['mec_date']['start']) and !empty($event->data->meta['mec_date']['start']))
            {
                $midnight_event = $this->main->is_midnight_event($event);
                ?>
                <div class="mec-single-event-date">
                    <i class="mec-sl-calendar"></i>
                    <h3 class="mec-date"><?php _e('Date', 'mec'); ?></h3>
                    <dl>
                    <?php if($midnight_event): ?>
                    <dd><abbr class="mec-events-abbr"><?php echo $this->main->dateify($event, $this->date_format1); ?></abbr></dd>
                    <?php else: ?>
                    <dd><abbr class="mec-events-abbr"><?php echo $this->main->date_label((trim($occurrence) ? array('date'=>$occurrence) : $event->date['start']), (trim($occurrence_end_date) ? array('date'=>$occurrence_end_date) : (isset($event->date['end']) ? $event->date['end'] : NULL)), $this->date_format1, ' - ', true, 0, $event); ?></abbr></dd>
                    <?php endif; ?>
                    </dl>
                    <?php echo $this->main->holding_status($event); ?>
                </div>

                <?php
                if(isset($event->data->meta['mec_hide_time']) and $event->data->meta['mec_hide_time'] == '0')
                {
                    $time_comment = isset($event->data->meta['mec_comment']) ? $event->data->meta['mec_comment'] : '';
                    $allday = isset($event->data->meta['mec_allday']) ? $event->data->meta['mec_allday'] : 0;
                    ?>
                    <div class="mec-single-event-time">
                        <i class="mec-sl-clock "></i>
                        <h3 class="mec-time"><?php _e('Time', 'mec'); ?></h3>
                        <i class="mec-time-comment"><?php echo (isset($time_comment) ? $time_comment : ''); ?></i>
                        <dl>
                        <?php if($allday == '0' and isset($event->data->time) and trim($event->data->time['start'])): ?>
                            <dd><abbr class="mec-events-abbr"><?php echo $event->data->time['start']; ?><?php echo (trim($event->data->time['end']) ? ' - '.$event->data->time['end'] : ''); ?></abbr></dd>
                        <?php else: ?>
                            <dd><abbr class="mec-events-abbr"><?php echo $this->main->m('all_day', __('All Day' , 'mec')); ?></abbr></dd>
                        <?php endif; ?>
                        </dl>
                    </div>
                    <?php
                }
            }
            ?>

            <?php
            // Event Cost
            $cost = (isset($event->data->meta) and isset($event->data->meta['mec_cost']) and trim($event->data->meta['mec_cost'])) ? $event->data->meta['mec_cost'] : '';
            if(isset($event->date) and isset($event->date['start']) and isset($event->date['start']['timestamp'])) $cost = MEC_feature_occurrences::param($event->ID, $event->date['start']['timestamp'], 'cost', $cost);

            if($cost)
            {
                ?>
                <div class="mec-event-cost">
                    <i class="mec-sl-wallet"></i>
                    <h3 class="mec-cost"><?php echo $this->main->m('cost', __('Cost', 'mec')); ?></h3>
                    <dl><dd class="mec-events-event-cost"><?php echo (is_numeric($cost) ? $this->main->render_price($cost, $event->ID) : $cost); ?></dd></dl>
                </div>
                <?php
            }
            ?>

            <?php
            // Event labels
            if(isset($event->data->labels) && !empty($event->data->labels))
            {
                $mec_items = count($event->data->labels);
                $mec_i = 0; ?>
                <div class="mec-single-event-label">
                    <i class="mec-fa-bookmark-o"></i>
                    <h3 class="mec-cost"><?php echo $this->main->m('taxonomy_labels', __('Labels', 'mec')); ?></h3>
                    <?php foreach($event->data->labels as $labels=>$label) :
                    $seperator = (++$mec_i === $mec_items ) ? '' : ',';
                    echo '<dl><dd style=color:"' . $label['color'] . '">' . $label["name"] . $seperator . '</dd></dl>';
                    endforeach; ?>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="mec-events-event-image">
            <?php echo $event->data->thumbnails['full']; ?>
            <?php if(isset($settings['featured_image_caption']) and $settings['featured_image_caption']) echo $this->main->display_featured_image_caption($event); ?>
        </div>

        <div class="col-md-4">

            <div class="mec-event-meta mec-color-before mec-frontbox <?php echo ((!$this->main->can_show_booking_module($event) and in_array($organizer_id, array('0', '1')) and !$more_info) ? 'mec-util-hidden' : ''); ?>">
                <?php
                // Event Organizer
                if($organizer_id and count($organizer))
                {
                    ?>
                    <div class="mec-single-event-organizer">
                        <?php if(isset($organizer['thumbnail']) and trim($organizer['thumbnail'])): ?>
                            <img class="mec-img-organizer" src="<?php echo esc_url($organizer['thumbnail']); ?>" alt="<?php echo (isset($organizer['name']) ? $organizer['name'] : ''); ?>">
                        <?php endif; ?>
                        <h3 class="mec-events-single-section-title"><?php echo $this->main->m('taxonomy_organizer', __('Organizer', 'mec')); ?></h3>
                        <dl>
                        <?php if(isset($organizer['thumbnail'])): ?>
                        <dd class="mec-organizer">
                            <i class="mec-sl-home"></i>
                            <h6><?php echo (isset($organizer['name']) ? $organizer['name'] : ''); ?></h6>
                        </dd>
                        <?php endif;
                        if(isset($organizer['tel']) && !empty($organizer['tel'])): ?>
                        <dd class="mec-organizer-tel">
                            <i class="mec-sl-phone"></i>
                            <h6><?php _e('Phone', 'mec'); ?></h6>
                            <a href="tel:<?php echo $organizer['tel']; ?>"><?php echo $organizer['tel']; ?></a>
                        </dd>
                        <?php endif;
                        if(isset($organizer['email']) && !empty($organizer['email'])): ?>
                        <dd class="mec-organizer-email">
                            <i class="mec-sl-envelope"></i>
                            <h6><?php _e('Email', 'mec'); ?></h6>
                            <a href="mailto:<?php echo $organizer['email']; ?>"><?php echo $organizer['email']; ?></a>
                        </dd>
                        <?php endif;
                        if(isset($organizer['url']) && !empty($organizer['url']) and $organizer['url'] != 'http://'): ?>
                        <dd class="mec-organizer-url">
                            <i class="mec-sl-sitemap"></i>
                            <h6><?php _e('Website', 'mec'); ?></h6>
                            <span><a href="<?php echo (strpos($organizer['url'], 'http') === false ? 'http://'.$organizer['url'] : $organizer['url']); ?>" class="mec-color-hover" target="_blank"><?php echo $organizer['url']; ?></a></span>
                        </dd>
                        <?php endif;
                        $organizer_description_setting = isset( $settings['organizer_description'] ) ? $settings['organizer_description'] : ''; $organizer_terms = get_the_terms($event->data, 'mec_organizer'); if($organizer_description_setting == '1' and is_array($organizer_terms) and count($organizer_terms)): foreach($organizer_terms as $organizer_term) { if ($organizer_term->term_id == $organizer['id'] ) {  if(isset($organizer_term->description) && !empty($organizer_term->description)): ?>
                        <dd class="mec-organizer-description">
                            <p><?php echo $organizer_term->description;?></p>
                        </dd>
                        <?php endif; } } endif; ?>
                        </dl>
                    </div>
                    <?php
                    $this->show_other_organizers($event); // Show Additional Organizers
                }
                ?>

                <!-- Register Booking Button -->
                <?php if($this->main->can_show_booking_module($event)): ?>
                    <a class="mec-booking-button mec-bg-color" href="#mec-events-meta-group-booking-<?php echo $this->uniqueid; ?>"><?php echo esc_html($this->main->m('register_button', __('REGISTER', 'mec'))); ?></a>
                <?php elseif($event_link): ?>
                    <a class="mec-booking-button mec-bg-color" target="_blank" href="<?php echo esc_url($event_link); ?>"><?php echo esc_html($this->main->m('register_button', __('REGISTER', 'mec'))); ?></a>
                <?php elseif($more_info): ?>
                    <a class="mec-booking-button mec-bg-color" target="<?php echo $more_info_target; ?>" href="<?php echo esc_url($more_info); ?>"><?php if($more_info_title) echo esc_html__($more_info_title, 'mec'); else echo esc_html($this->main->m('register_button', __('REGISTER', 'mec'))); ?></a>
                <?php endif; ?>
            </div>

            <!-- Local Time Module -->
            <?php echo $this->main->module('local-time.details', array('event'=>$event)); ?>

            <div class="mec-event-meta mec-color-before mec-frontbox">

                <?php do_action('mec_single_virtual_badge', $event->data ); ?>
                <?php do_action('mec_single_zoom_badge', $event->data ); ?>

                <?php
                // Event Location
                if($location_id and count($location))
                {
                    ?>
                    <div class="mec-single-event-location">
                        <?php if($location['thumbnail']): ?>
                        <img class="mec-img-location" src="<?php echo esc_url($location['thumbnail'] ); ?>" alt="<?php echo (isset($location['name']) ? $location['name'] : ''); ?>">
                        <?php endif; ?>
                        <i class="mec-sl-location-pin"></i>
                        <h3 class="mec-events-single-section-title mec-location"><?php echo $this->main->m('taxonomy_location', __('Location', 'mec')); ?></h3>
                        <dl>
                        <dd class="author fn org"><?php echo $this->get_location_html($location); ?></dd>
                        <dd class="location"><address class="mec-events-address"><span class="mec-address"><?php echo (isset($location['address']) ? $location['address'] : ''); ?></span></address></dd>

                        <?php if(isset($location['url']) and trim($location['url'])): ?>
                        <dd class="mec-location-url">
                            <i class="mec-sl-sitemap"></i>
                            <h6><?php _e('Website', 'mec'); ?></h6>
                            <span><a href="<?php echo (strpos($location['url'], 'http') === false ? 'http://'.$location['url'] : $location['url']); ?>" class="mec-color-hover" target="_blank"><?php echo $location['url']; ?></a></span>
                        </dd>
                        <?php endif;
                        $location_description_setting = isset( $settings['location_description'] ) ? $settings['location_description'] : ''; $location_terms = get_the_terms($event->data, 'mec_location'); if($location_description_setting == '1' and is_array($location_terms) and count($location_terms)): foreach($location_terms as $location_term) { if ($location_term->term_id == $location['id'] ) {  if(isset($location_term->description) && !empty($location_term->description)): ?>
                        <dd class="mec-location-description">
                            <p><?php echo $location_term->description;?></p>
                        </dd>
                        <?php endif; } } endif; ?>
                        </dl>
                    </div>
                    <?php
                }
                ?>

                <?php
                // Event Categories
                if(isset($event->data->categories) and !empty($event->data->categories))
                {
                    ?>
                    <div class="mec-single-event-category">
                        <i class="mec-sl-folder"></i>
                        <dt><?php echo $this->main->m('taxonomy_categories', __('Category', 'mec')); ?></dt>
                        <?php
                        foreach($event->data->categories as $category)
                        {
                            $color = ((isset($category['color']) and trim($category['color'])) ? $category['color'] : '');

                            $color_html = '';
                            if($color) $color_html .= '<span class="mec-event-category-color" style="--background-color: '.esc_attr($color).';background-color: '.esc_attr($color).'">&nbsp;</span>';

                            $icon = (isset($category['icon']) ? $category['icon'] : '');
                            $icon = isset($icon) && $icon != '' ? '<i class="' . $icon . ' mec-color"></i>' : '<i class="mec-fa-angle-right"></i>';

                            echo '<dl><dd class="mec-events-event-categories"><a href="'.get_term_link($category['id'], 'mec_category').'" class="mec-color-hover" rel="tag">' . $icon . $category['name'] . $color_html .'</a></dd></dl>';
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
                <?php
                    // More Info
                    if($more_info)
                    {
                        ?>
                        <div class="mec-event-more-info">
                            <i class="mec-sl-info"></i>
                            <h3 class="mec-cost"><?php echo $this->main->m('more_info_link', __('More Info', 'mec')); ?></h3>
                            <dl><dd class="mec-events-event-more-info"><a class="mec-more-info-button mec-color-hover" target="<?php echo $more_info_target; ?>" href="<?php echo esc_url($more_info); ?>"><?php echo $more_info_title; ?></a></dd></dl>
                        </div>
                        <?php
                    }
                ?>

            </div>

            <?php

            if(!empty($this->main->module('speakers.details', array('event'=>$event)))) {
                ?>
                <div class="mec-color-before mec-frontbox">
                    <?php echo $this->main->module('speakers.details', array('event'=>$event)); ?>
                </div>
                <?php
            }
            ?>

            <!-- Attendees List Module -->
            <?php echo $this->main->module('attendees-list.details', array('event'=>$event)); ?>

            <!-- Next Previous Module -->
            <?php echo $this->main->module('next-event.details', array('event'=>$event)); ?>
        </div>

        <div class="col-md-8">

            <div class="mec-event-content">
                <div class="mec-single-event-description mec-events-content"><?php echo $this->main->get_post_content($event); ?></div>
            </div>

            <?php do_action('mec_single_after_content', $event ); ?>

            <!-- Custom Data Fields -->
            <?php $this->display_data_fields($event); ?>

            <!-- Links Module -->
            <?php echo $this->main->module('links.details', array('event'=>$event)); ?>

            <!-- Google Maps Module -->
            <div class="mec-events-meta-group mec-events-meta-group-gmap">
                <?php echo $this->main->module('googlemap.details', array('event'=>$this->events)); ?>
            </div>

            <!-- Export Module -->
            <?php echo $this->main->module('export.details', array('event'=>$event)); ?>

            <!-- Countdown module -->
            <?php if($this->main->can_show_countdown_module($event)): ?>
            <div class="mec-events-meta-group mec-events-meta-group-countdown">
                <?php echo $this->main->module('countdown.details', array('event'=>$this->events)); ?>
            </div>
            <?php endif; ?>

            <!-- Discord Connect -->
            <?php

            /**
             * 
             * 
             * Identify Button Status on page load. Buttons only appear when discord channels have been established and we are 30 minutes from the start of the event.
             * 
             * 1. Join Server (green) - server is not at limit and user is not attached to any other event instances.
             * 2. Leave Server (yellow) - User is current attached to server instance.
             * 3. Server Full (red) - Server is at limit.
             */

            ?>
            <div id="ds-mec-discord-event">
                <div id="ds-mec-discord-user-buttons">
                    <?php echo $discordEvent->getEventButton( $event->ID ); ?>
                </div>
                <!-- Event Styles. -->
                <style>
                        .event-action-button {
                            position: relative;
                            padding: 8px 16px;
                            background: #009579;
                            border: none;
                            outline: none;
                            border-radius: 2px;
                            cursor: pointer;
                        }

                        .event-action-button:active {
                            background: #007a63;
                        }

                        .button__text {
                            font: bold 20px "Quicksand", san-serif;
                            color: #ffffff;
                            transition: all 0.2s;
                        }

                        .button--loading .button__text {
                            visibility: hidden;
                            opacity: 0;
                        }

                        .button--loading::after {
                            content: "";
                            position: absolute;
                            width: 16px;
                            height: 16px;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            margin: auto;
                            border: 4px solid transparent;
                            border-top-color: #ffffff;
                            border-radius: 50%;
                            animation: button-loading-spinner 1s ease infinite;
                        }

                        @keyframes button-loading-spinner {
                            from {
                                transform: rotate(0turn);
                            }

                            to {
                                transform: rotate(1turn);
                            }
                        }
                    </style>
                <!-- Add our script -->
                <?php do_action( 'ds_mec_discord_join_server_script', $event->ID ); ?>
            </div>

            <!-- Hourly Schedule -->
            <?php $this->display_hourly_schedules_widget($event); ?>

            <?php do_action( 'mec_before_booking_form', get_the_ID() ); ?>
			<!-- Booking Module -->
            <?php if($this->main->is_sold($event) and count($event->dates) <= 1): ?>
            <div id="mec-events-meta-group-booking-<?php echo $this->uniqueid; ?>"class="mec-sold-tickets warning-msg"><?php _e('Sold out!', 'mec'); do_action( 'mec_booking_sold_out',$event, null,null,array($event->date) );?> </div>
            <?php elseif($this->main->can_show_booking_module($event)): ?>
            <div id="mec-events-meta-group-booking-<?php echo $this->uniqueid; ?>" class="mec-events-meta-group mec-events-meta-group-booking">
                <?php
                if(isset($settings['booking_user_login']) and $settings['booking_user_login'] == '1' and !is_user_logged_in() ) {
                    echo do_shortcode('[MEC_login]');
                } elseif ( isset($settings['booking_user_login']) and $settings['booking_user_login'] == '0' and !is_user_logged_in() and isset($booking_options['bookings_limit_for_users']) and $booking_options['bookings_limit_for_users'] == '1' ) {
                    echo do_shortcode('[MEC_login]');
                } else {
                    echo $this->main->module('booking.default', array('event'=>$this->events));
                }
                ?>
            </div>
            <?php endif ?>

            <!-- Tags -->
            <div class="mec-events-meta-group mec-events-meta-group-tags">
                <?php echo get_the_term_list(get_the_ID(), apply_filters('mec_taxonomy_tag', ''), __('Tags: ', 'mec'), ', ', '<br />'); ?>
            </div>

        </div>
    </article>
</div>
<script>
jQuery(".mec-speaker-avatar a").on('click', function(e)
{
    e.preventDefault();

    var id =  jQuery(this).attr('href');
    var instance = lity(id);
    jQuery(document).on('lity:close', function(event, instance)
    {
        jQuery( ".mec-hourly-schedule-speaker-info" ).addClass('lity-hide');
    });
});
</script>