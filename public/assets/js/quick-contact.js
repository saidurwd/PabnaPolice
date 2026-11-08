/* Footer "Quick Contact" form: client-side checks, then POST to the URL in #submit[data-url]. */
$(function () {
    $('#submit').click(function () {
        var name = $('#name').val();
        var name_regex = /^([a-z A-Z .])+$/i;
        var email_address = $('#email').val();
        var email_regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i;
        var nid = $('#nid').val();
        var nid_regex = /^([0-9])/;
        var message = $('#message').val();
        var messsage_regex = /^([a-z A-Z . , () @ 0-9])+$/i;
        var phone = $('#phoneNumber').val();
        var phone_regex = /^(([0-9]*)|(([0-9]*)\+([0-9]*)))$/;
        if ($('#name').val() == '') {
            $('.removeSpan').remove();
            $('.name').append('<span class="removeSpan absolute error_msg">Name Required</span>')
        } else if (!name_regex.test(name)) {
            $('.removeSpan').remove();
            $('.name').append('<span class="removeSpan absolute error_msg">Please enter valid name.</span>')
        } else if ($('#email').val() == '') {
            $('.removeSpan').remove();
            $('.email').append('<span class="removeSpan absolute error_msg">Email Required.</span>')
        } else if (!email_regex.test(email_address)) {
            $('.removeSpan').remove();
            $('.email').append('<span class="removeSpan absolute error_msg">Please enter valid email.</span>')
        } else if ($('#phoneNumber').val() == '') {
            $('.removeSpan').remove();
            $('.phone').append('<span class="removeSpan absolute error_msg">Telephone number required.</span>')
        } else if (!phone_regex.test(phone)) {
            $('.removeSpan').remove();
            $('.phone').append('<span class="removeSpan absolute error_msg">Please enter valid phone number.</span>')
        } else if ($('#nid').val() == '') {
            $('.removeSpan').remove();
            $('.nid').append('<span class="removeSpan absolute error_msg">NID number required.</span>')
        } else if (!nid_regex.test(nid)) {
            $('.removeSpan').remove();
            $('.nid').append('<span class="removeSpan absolute error_msg">Please enter valid NID Number.</span>')
        } else if ($('#message').val() == '') {
            $('.removeSpan').remove();
            $('.message').append('<span class="removeSpan absolute error_msg">Message input required.</span>')
        } else if (!messsage_regex.test(message)) {
            $('.removeSpan').remove();
            $('.message').append('<span class="removeSpan absolute error_msg">Please type valid Message.</span>')
        } else {
            $('.removeSpan').remove();
            $(".removeSuccessMessage").remove();
            $(".removeErrorMessage").remove();
            $(".button_1right_iconImage").show();

            var query = {
                'name': $('#name').val(),
                'email': $("#email").val(),
                'phone': $("#phoneNumber").val(),
                'nid': $("#nid").val(),
                'message': $("#message").val(),
                '_token': $('#csrf-token').val()
            };

            $.ajax({
                type: "POST",
                url: $('#submit').data('url'),
                data: query,
                dataType: 'json',
                success: function (json) {
                    $(".sucessMessage").fadeIn();
                    $(".button_1right_iconImage").hide();
                    $(".sucessMessage").append('<span class="removeSuccessMessage">Thank you for your feedback</span>');
                    $('#name').val('');
                    $("#email").val('');
                    $("#phoneNumber").val('');
                    $("#nid").val('');
                    $("#message").val('');
                    setTimeout(function () {
                        $(".sucessMessage").fadeOut();
                    }, 10000);
                },
                error: function (json) {
                    $(".errorMessage").fadeIn();
                    $(".button_1right_iconImage").hide();
                    $(".errorMessage").append('<span class="removeErrorMessage">Something went wrong please try again!!</span>');
                    setTimeout(function () {
                        $(".errorMessage").fadeOut();
                    }, 10000);
                }
            });
        }
    });
});
