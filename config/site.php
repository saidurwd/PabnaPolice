<?php

/**
 * Site settings definition. Values live in the `setting` table and are edited in
 * Admin > Site Settings. The `default` is what the old Yii theme had hardcoded.
 * type: text | textarea | number | url
 */
return [
    'General' => [
        'site_name' => ['label' => 'Site name', 'type' => 'text', 'default' => 'Superintendent of police, Pabna'],
        'admin_email' => ['label' => 'Admin e-mail', 'type' => 'text', 'default' => 'sppabna@police.gov.bd'],
        'webmail_url' => ['label' => 'Webmail URL', 'type' => 'url', 'default' => 'https://webmail.police.gov.bd/owa'],
        'meta_keywords' => ['label' => 'Meta keywords', 'type' => 'text', 'default' => 'Bangladesh Police'],
        'meta_description' => ['label' => 'Meta description', 'type' => 'text', 'default' => ''],
        'footer_credit' => ['label' => 'Footer credit', 'type' => 'text', 'default' => 'Developed by Momtaj Trading(Pvt.) Ltd.'],
        'page_size' => ['label' => 'Items per page (content / documents)', 'type' => 'number', 'default' => '25'],
        'gallery_page_size' => ['label' => 'Items per page (gallery)', 'type' => 'number', 'default' => '20'],
    ],
    'Social links' => [
        'facebook_url' => ['label' => 'Facebook URL', 'type' => 'url', 'default' => 'https://www.facebook.com/SPPABNA'],
        'youtube_url' => ['label' => 'YouTube URL', 'type' => 'url', 'default' => 'https://www.youtube.com/channel/UCiEciIZfqbetHCJe6oCi6FQ'],
        'twitter_url' => ['label' => 'Twitter URL', 'type' => 'url', 'default' => 'https://twitter.com/bd_police'],
    ],
    'Homepage' => [
        'header_banner_id' => ['label' => 'Header banner (Banner ID)', 'type' => 'number', 'default' => '20', 'help' => 'Image shown in the logo bar at the top of every page.'],
        'home_slider_category' => ['label' => 'Slider banners (Banner Category ID)', 'type' => 'number', 'default' => '1'],
        'home_units_category' => ['label' => 'Units carousel (Banner Category ID)', 'type' => 'number', 'default' => '3'],
        'home_igp_content' => ['label' => 'SP message / photo (Content ID)', 'type' => 'number', 'default' => '2'],
        'home_mission_content' => ['label' => 'Mission box (Content ID)', 'type' => 'number', 'default' => '1'],
        'home_vision_content' => ['label' => 'Vision box (Content ID)', 'type' => 'number', 'default' => '3'],
        'home_history_content' => ['label' => 'History box (Content ID)', 'type' => 'number', 'default' => '4'],
        'home_recent_category' => ['label' => 'Recent activities (Category ID)', 'type' => 'number', 'default' => '3'],
        'home_recent_limit' => ['label' => 'Recent activities: number of items', 'type' => 'number', 'default' => '4'],
        'home_message_heading' => ['label' => 'Heading: SP message section', 'type' => 'text', 'default' => 'পুলিশ সুপার মহোদয় সর্ম্পকিত'],
        'home_units_heading' => ['label' => 'Heading: units section', 'type' => 'text', 'default' => 'পাবনা জেলা পুলিশের ইউনিট সমুহ'],
        'home_links_heading' => ['label' => 'Heading: important links section', 'type' => 'text', 'default' => 'গুরুত্বপূর্ণ লিংক সমুহ'],
        'control_room_title' => ['label' => 'Control room box: title', 'type' => 'text', 'default' => 'পুলিশ কন্ট্রোল রুম, পাবনা'],
        'control_room_numbers' => ['label' => 'Control room box: numbers (one per line)', 'type' => 'textarea', 'default' => "০১৩২০-১২৯৪৯৮\n০৭৩১-৬৫০৭৭\n০৭৩১-৬৫৬৫৪(ফ্যাক্স)\n০১৩২০-১২৯৮৫৭১-DIO-1(DSB)"],
        'map_heading' => ['label' => 'Map section heading', 'type' => 'text', 'default' => 'গুগল ম্যাপে পাবনা জেলা পুলিশ সুপারের কর্যালয়'],
        'map_embed_url' => ['label' => 'Google Maps embed URL', 'type' => 'textarea', 'default' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3638.530841145013!2d89.27887201446494!3d24.22320567646765!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39fc28d9982528eb%3A0x6771ffe3ebc22398!2z4Kaq4KeB4Kay4Ka_4Ka2IOCmuOCngeCmquCmvuCmsCDgpoXgpqvgpr_gprg!5e0!3m2!1sbn!2sbd!4v1596615727600!5m2!1sbn!2sbd'],
    ],
    'Navigation' => [
        'nav_categories' => ['label' => 'Main menu categories (Category IDs, comma separated, in order)', 'type' => 'text', 'default' => '6,14,8,7,9,13,10,12', 'help' => 'Each becomes a drop-down listing its contents and sub-categories.'],
        'nav_listing_categories' => ['label' => 'Main menu: drop-downs that show sub-category contents (Category IDs, comma separated)', 'type' => 'text', 'default' => '6', 'help' => 'For these menus, sub-categories with "Show sub-menu listing = Yes" expand to list their contents.'],
        'nav_gallery_category' => ['label' => 'Main menu: gallery drop-down (Banner Category ID)', 'type' => 'number', 'default' => '2'],
        'nav_document_category' => ['label' => 'Main menu: document link (Document Category ID)', 'type' => 'number', 'default' => '15'],
    ],
];
