<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<h2><?php _e ( esc_html('Hubspot Plugin Settings'), 'hubspot-plugin-advance' );  ?></h2>
<form action="" method="post">
    <?php wp_nonce_field('hubspot_action','hubspot_nonce_field'); ?>
    <?php if (isset($_POST['save-hubspot'])): ?>
        <div class="one-form-block">
            <p class="changes_saved"><?php _e ( esc_html('The changes has been saved!'), 'hubspot-plugin-advance' );  ?></p>
        </div>
    <?php endif ?>
    
    <div class="one-form-block">
        <label for="">Active</label>
        <div class="input-wrapper">
            <select name="use_hubspot">
                <option <?php if ($use_hubspot == 'no'): ?>selected="selected"<?php endif ?> value="no"><?php _e ( esc_html('No'), 'hubspot-plugin-advance' );  ?></option>
                <option <?php if ($use_hubspot == 'yes'): ?>selected="selected"<?php endif ?> value="yes"><?php _e ( esc_html('Yes'), 'hubspot-plugin-advance' );  ?></option>
            </select>           
        </div>
    </div>
    
    
    <div class="one-form-block">
        <label for="rss_url"><?php _e ( esc_html('RSS URL'), 'hubspot-plugin-advance' );  ?></label>
        <div class="input-wrapper">
            <input type="text" name="rss_url" value="<?php echo esc_url($rss_url) ?>" id="rss_url" />
        </div>
    </div>
    <div class="one-form-block">
        <label for=""><?php _e ( esc_html('Hubspot API Key'), 'hubspot-plugin-advance' );  ?></label>
        <div class="input-wrapper">
            <input type="text" name="hubspot_api_key" value="<?php echo esc_html($hubspot_api_key) ?>" id="" />
        </div>
    </div>

    <div class="one-form-block">
        <label for=""><?php _e ( esc_html('New post status'), 'hubspot-plugin-advance' );  ?></label>
        <div class="input-wrapper">
            <select name="new_post_status">
                <option <?php if ($new_post_status == 'draft'): ?>selected="selected"<?php endif ?> value="draft"><?php _e ( esc_html('Draft'), 'hubspot-plugin-advance' );  ?></option>
                <option <?php if ($new_post_status == 'publish'): ?>selected="selected"<?php endif ?> value="publish"><?php _e ( esc_html('Published'), 'hubspot-plugin-advance' );  ?></option>
            </select>           
        </div>
    </div>

    <div class="one-form-block">
        <label for=""><?php _e ( esc_html('Post As'), 'hubspot-plugin-advance' );  ?></label>
        <div class="input-wrapper">
            <select name="post_as">
                <option <?php if ($post_as == 'blog_post'): ?>selected="selected"<?php endif ?> value="blog_post"><?php _e ( esc_html('Blog Post'), 'hubspot-plugin-advance' );  ?></option>
                <option <?php if ($post_as == 'social_media_message'): ?>selected="selected"<?php endif ?> value="social_media_message"><?php _e ( esc_html('Social Media Message'), 'hubspot-plugin-advance' );  ?></option>
            </select>           
        </div>
    </div>

    <?php if (!empty($channels)): ?>
        <div class="one-form-block">
            <label for=""><?php _e ( esc_html('Social Media Publishing Channel'), 'hubspot-plugin-advance' );  ?></label>
            <p><?php _e ( esc_html('Hold CTRL button to select more then one'), 'hubspot-plugin-advance' );  ?></p>
            <div class="input-wrapper">
                <select class="big" multiple="mutiple" name="publishing_channel[]">
                    <?php foreach ($channels as $channel): ?>
                        <option <?php if (!empty($publishing_channel) && in_array($channel->channelGuid, $publishing_channel)): ?>selected="selected"<?php endif ?> value="<?php echo $channel->channelGuid ?>"><?php echo $channel->name ?> (<?php echo $channel->type ?>)</option>
                    <?php endforeach ?>
                </select>           
            </div>
        </div>
    <?php endif ?>

   
  

    <div class="one-form-block">
        <input type="submit" class="button button-primary" name="save-hubspot" value="<?php _e ( esc_html('Save'), 'hubspot-plugin-advance' );  ?>" />
    </div>
</form>
<script>
    (function ($)
    {
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

        $(document).ready(function ()
        {
            $('#get-pardot').click(function (e)
            {
                e.preventDefault();
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'get_pardot',
                        pardot_email: $('#pardot_email').val(),
                        pardot_password: $('#pardot_password').val(),
                        pardot_user_api_key: $('#pardot_user_api_key').val()
                    },
                    type: 'post',
                    success: function (data)
                    {
                        if(data==='-1')
                        {
                            alert('Wrong credentials!');
                        }
                        else
                        {
                            var input = $('input[name="pardot_api_key"]');
                            $(input).val(data);
                            $(input).css('background','#efe');
                            $(input).css('border','1px solid #f44');
                            setTimeout(function()
                            {
                                $(input).css('background','');
                                $(input).css('border','');
                            },1000);
                        }
                    }
                });
            });

        });
    })(jQuery);
</script>
<style>

    .very-small
    {
        font-size: 12px;
    }

    .changes_saved
    {
        color: #5b5;
        font-size: 18px;
        font-weight: bold;
    }    

    .one-form-block
    {
        margin-bottom: 20px;
    }

    #get_pardot
    {
        padding: 30px;
        background: #efe;
        border: 1px solid #000;
        width: 100%;
        max-width: 500px;
        box-sizing: border-box;
    }

    .one-form-block label
    {
        font-weight: bold;
    }

    .one-form-block input[type="text"],
    .one-form-block input[type="password"],
    .one-form-block select
    {
        width: 100%;
        max-width: 500px;
        height: 35px;
        padding: 5px;
        border-radius: 3px;
    }

    .one-form-block select.big
    {
        min-height: 200px;
    }

</style>