<?php
/*
Template Name: Custom CRUD
*/
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<div class="custom-crud-wrapper">
    <?php 
        echo do_shortcode('[custom_crud]');
    ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
