jQuery(document).ready(function ($) {
    $('.thumbs-up-button').click(function (e) {
        e.preventDefault();
        var button = $(this);

        // Check if the button already has the 'liked' class 

        if (button.hasClass('liked')) {

            // Show confirmation dialog
            var confirmUnlike = confirm('You have already liked this post. Do you want to unlike it?');
            if (confirmUnlike) {
                var postId = button.data('post-id')

                // Send AJAX request to unlike the post

                $.ajax({
                    cache: false,
                    url: thumbsUpAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'thumbs_down', // A separate action for unliking
                        post_id: postId,
                        nonce: thumbsUpAjax.nonce,
                        timestamp: new Date().getTime()
                    },
                    success: function (response) {
                        if (response.success) {
                            button.siblings('.likes-count').text(response.data.likes);
                            button.removeClass('liked');
                        } else {
                            console.log('Error:', response.data.message);
                        }
                    }
                });
            }
            return;
        }

        // If not liked, proceed to like the post

        var postId = button.data('post-id');

        $.ajax({
            cache: false,
            url: thumbsUpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'thumbs_up',
                post_id: postId,
                nonce: thumbsUpAjax.nonce,
                timestamp: new Date().getTime()
            },
            success: function (response) {
                if (response.success) {
                    button.siblings('.likes-count').text(response.data.likes);
                    button.addClass('liked');
                } else {
                    console.log('Error:', response.data.message);
                }
            }
        });
    });
});