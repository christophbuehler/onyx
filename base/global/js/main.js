$(function() {
    var alertTimer;
    $('body').append('<div id="alertBox"><div><div class="title">Notification</div><div class="text"></div></div></div>');
    window.alert = function(text) {
        clearTimeout(alertTimer);

        $('#alertBox .text').html(text);
        $('#alertBox').show();

        alertTimer = setTimeout(function() {
            $('#alertBox').hide();
        }, 2000);
        $('#alert')
    };
});
