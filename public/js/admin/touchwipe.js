$(function(){
    var sidr_width = 0;

    $('#btnnav').sidr({
        renaming: false,
        side: 'right',
        source: '#nav',
        onOpen: function() {
            $('#header').animate({left: '-260px', right: '260px'}, 200);
            $('#btnnav_tab').animate({right: '260px'}, 200);
            $('#sidr_overlay').fadeIn();
            sidr_width = $(window).width();

            var y = $('body').scrollTop();
            $('body').css({
                position:'fixed',
                top: '-' + y + 'px'
            });
        },
        onClose: function() {
            $('#header').animate({left: '0', right: '0'}, 100);
            $('#btnnav_tab').animate({right: '0'}, 100);
            $('#sidr_overlay').fadeOut();
            sidr_width = 0;

            var y = $('body').css('top');
            $('body').css({
                position:'',
                top: ''
            }).scrollTop(parseInt(y) * -1);
        }
    });

    $('#sidr_overlay').click(function() {
        sidr_width && $.sidr('close', 'sidr');
    });

    $(window).resize(function () {
        sidr_width && sidr_width !== $(window).width() && $.sidr('close', 'sidr');
    });
});
