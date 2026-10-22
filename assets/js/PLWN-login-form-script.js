
jQuery(document).ready(function ($) {
    $('#mobile_number').on('input', function () {
        var value = $(this).val();
        value = value.replace(/[۰-۹٠-٩]/g, function (digit) {
            var code = digit.charCodeAt(0);
            return String(code - (code >= 0x06F0 ? 0x06F0 : 0x0660));
        });
        value = value.replace(/[\s\p{Z}\p{Cf}\p{Pd}()]/gu, '');
        if ($(this).val() !== value) {
            $(this).val(value);
        }
    });
    $('#PLWN-login-form').on('submit', function (event) {
        event.preventDefault();
        $('button#PLWN-login-form-submit').html('<span class="loader"></span>');
        $('button#PLWN-login-form-submit').prop('disabled', true);
        $('#PLWN-login-form-box input#mobile_number').prop('disabled', true);
        var data = {};
        if ($('#PLWN-login-form-box input#otp_code').length > 0) {
            data = {
                action: 'PLWN_ajax_send_sms',
                nonce: ajax_object.nonce,
                mobile_number: $('#PLWN-login-form-box input#mobile_number').val(),
                otp_code: $('#PLWN-login-form-box input#otp_code').val()
            };
        } else {
            data = {
                action: 'PLWN_ajax_send_sms',
                nonce: ajax_object.nonce,
                mobile_number: $('#PLWN-login-form-box input#mobile_number').val()
            };
        }
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: { action: 'PLWN_ajax_get_csrf_token' },
            success: function (tokenResponse) {
                if (!tokenResponse.success || !tokenResponse.data || !tokenResponse.data.csrf_token) {
                    $('#respone_box').text('An error occurred while sending the form.');
                    $('button#PLWN-login-form-submit').html('Try again').prop('disabled', false);
                    $('#PLWN-login-form-box input#mobile_number').prop('disabled', false);
                    return;
                }

                data.csrf_token = tokenResponse.data.csrf_token;
                $.ajax({
                    type: 'POST',
                    url: ajax_object.ajax_url,
                    data: data,
                    success: function (response) {
                        if (response.status == 'success') {
                            counter_time_callback();
                            $('#respone_box').html(response.message);
                            $('button#PLWN-login-form-submit').html(response.text);
                            $('button#PLWN-login-form-submit').prop('disabled', false);
                            var newInputotp = response.new_input;
                            var newUrl = response.url;
                            if (newUrl) {
                                window.location.href = newUrl;
                            }
                            $('#container-inputs').append(newInputotp).fadeIn(500);
                        } else {
                            $('#respone_box').html(response.message);
                            $('button#PLWN-login-form-submit').html(response.text);
                            $('button#PLWN-login-form-submit').prop('disabled', false);
                            $('#PLWN-login-form-box input#mobile_number').prop('disabled', false);
                        }
                    },
                    error: function () {
                        $('#respone_box').text('An error occurred while sending the form.');
                        $('button#PLWN-login-form-submit').html('Try again').prop('disabled', false);
                        $('#PLWN-login-form-box input#mobile_number').prop('disabled', false);
                    }
                });
            },
            error: function () {
                $('#respone_box').text('An error occurred while sending the form.');
                $('button#PLWN-login-form-submit').html('Try again').prop('disabled', false);
                $('#PLWN-login-form-box input#mobile_number').prop('disabled', false);
            }
        });
    });

    $('#PLWN-login-form-box #resend_box_btn').on('click', function (event) {
        location.reload();
    });

    function counter_time_callback() {
        if (typeof counterElement == 'undefined') {
            jQuery('#PLWN-login-form-box .resend_box').css("display", "block");
            const counterElement = document.getElementById('time-counter');
            let fistTimeCounter = counterElement.textContent;
            const intervalTimer = setInterval(() => {
                if (fistTimeCounter > 0) {
                    fistTimeCounter--;
                    counterElement.textContent = fistTimeCounter;
                } else {
                    clearInterval(intervalTimer);
                    jQuery('#PLWN-login-form-box .resend_box').css("display", "none");
                    jQuery('#PLWN-login-form-box #resend_box_btn').css("display", "block");
                }
            }, 1000);
        }
    }
});