$(document).ready(function() {
    $('.phone').mask('+7 (999) 999-99-99');

    $('a.btn-modal').fancybox({
        beforeShow: function() {
            $('.contacts_header_box, .menu_box').css('padding-right', scrollbarWidth());
        },
        afterClose: function () {
            $('.contacts_header_box, .menu_box').css('padding-right', '');
        }
    });

    function scrollbarWidth() {
        var block = $('<div>').css({'height': '50px', 'width': '50px'}),
            indicator = $('<div>').css({'height': '200px'});

        $('body').append(block.append(indicator));
        var w1 = $('div', block).innerWidth();
        block.css('overflow-y', 'scroll');
        var w2 = $('div', block).innerWidth();
        $(block).remove();
        return (w1 - w2);
    }

    /* Автоматическое проставление id и for для label и input. */
    var bindLabels, guid;
    guid = function () {
        var s4;
        s4 = function () {
            return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
        };
        return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
    };
    window.guid = guid;
    bindLabels = function () {
        $('input[type="checkbox"], input[type="radio"]').not('[id]').each(function () {
            var id = guid();
            th = $(this);
            th.attr('id', id);
            if (th.next().is('label')) {
                return th.next().attr('for', id);
            }
        });
        return $('label').not('[for]').each(function () {
            var id = guid();
            th = $(this);
            if (th.next().is('input,textarea,select')) {
                th.attr('for', id);
                return th.next().attr('id', id);
            } else if (th.prev().is('input,textarea,select')) {
                th.attr('for', id);
                return th.prev().attr('id', id);
            }
        });
    };
    bindLabels();

    /*Запись данных в форму в модальном окне*/
    $('.btn-modal').on('click', '', function () {
        var th = $(this);
        if (th.data("reason")) {
            $('.form_up .callOrderReason').val(th.data("reason"));
        }
        if (th.data("goal")) {
            $('.form_up .goalMetrika').val(th.data("goal"));
        }
        if (th.data("header")) {
            $('.form_up .callOrderHeader').text(th.data("header"));
        }
    });

    /*Мультигородность*/
    var city_width = $('.city-arrow').innerWidth();
    $('.hint_city').css('left',-(city_width/2-5));

    /*Ajax подгрузка услуг*/
    if($(window).width() < 769) {
        //var id = $(this).data('id') || 0;
        //var migxid = $(this).data('migxid') || 0;
        setTimeout(function () {
            $.ajax({
                url: '/assets/ajax.php',
                data: 'action=getcontent1',
                success: function (data) {
                    $('#ajax-team').html(data);
                    $('.btn.btn_team').remove();
                }
            });
        }, 2000);
    } else {
        $(document).on('click','.btn.btn_team.show',function(){
            $.ajax({
                url: '/assets/ajax.php',
                data: 'action=getcontent1',
                success: function (data) {
                    $('#ajax-team').html(data);
                    $('.btn.btn_team').remove();
                }
            });
        });
    }



    $(document).on('click','.btn.btn_team.hide',function(){
        $('#ajax-team').slideToggle();
        $(this).text($(this).text() == 'Свернуть' ? 'Посмотреть еще' : 'Свернуть');
    });

    $(document).on('click','.all-services__button',function(){
        $('.services_hide').slideToggle();
        $('.all-services').hide();
    });

    $(".hint_button_close, .city_item").on('click', function () {
        $(".hint_city").hide();
        $.cookie('hint_button_close', 'yes', { expires: 365 });
    });

    if ($.cookie('hint_button_close') != 'yes') {
        $(".hint_city").show();
    }

    /*Мобильное меню*/
    $('.btn-mobile-menu').on('click', function () {
        $('.toogle_menu').slideToggle();
        $('.menu_container').toggleClass('open');
    });

    var current1 = ('.section_form.active'),
        checkbox = false,
        th = $(this);

    $(document).on('click','.form_quiz .section_form.active .checkbox-input', function () {
        var status = 0;

        $('.section_form.active .checkbox-input').each(function(i) {
            var th = $(this);
            if (th.prop('checked') == true) {
                status++;
            }
        });
        if (status === 0) {
            checkbox = false;
        }
        else {
            checkbox = true;
        }
        if (th.prop('checked') == true) {
            $('.next_form',current1).prop('disabled',false);
        }
        else if ((th.prop('checked') == false) && (!checkbox))  {
            $('.next_form',current1).prop('disabled',true);
        }
    });

    $(document).on('input', '.form_quiz .section_form.active input' ,function(){
        if ($(this).val()) {
            th.find('.next_form').prop('disabled',false);
            checkbox = true;
        }
        else {
            th.find('.next_form').prop('disabled',true);
            checkbox = false;
        }
    });

    $(document).on('mouseup','.next_form', function() {
        //event.preventDefault();
        var checkinput = true,
            current = $('.form_quiz .section_form.active');

        $('.section_form.active [required]').map(function () {
            var t = $(this);
            if (($(this).val().length === 0)) {
                checkinput = false;
                t.addClass('has-error');
            }
            else {
                t.removeClass('has-error');
            }
        });

        if ((checkinput) && (checkbox)) {
            current.next().addClass('active');
            current.removeClass('active');
        }

        if ($('.form_quiz .section_form.active input').val()) {
            th.find('.next_form').prop('disabled',false);
            checkbox = true;
        }
        else {
            th.find('.next_form').prop('disabled',true);
            checkbox = false;
        }
    });

    $(document).on('mouseup','.prev_form', function() {
        //event.preventDefault();
        var current = $('.form_quiz .section_form.active');
        checkbox = true;
        current.removeClass('active');
        current.prev().addClass('active');

        if ($('.form_quiz .section_form.active input').val()) {
            th.find('.next_form').prop('disabled',false);
            checkbox = true;
        }
        else {
            th.find('.next_form').prop('disabled',true);
            checkbox = false;
        }
    });

    /*$("#form-success").on("hide.bs.modal", function () {
        $('.form-modal__success').remove();
    });*/


    $('.develop-item').on('click' ,function(){
        $($(this).data('target') + ' .industry-img img').each(function(){
            var $th = $(this);
            $th.attr('src', $th.data('src'));
        });
    });
});


$('.first-screen-info .btn').on('click' ,function(){
    $.fancybox.close();
});

$('.menu-block-svg').on('click', '', function () {
    $(this).toggleClass('open').next().slideToggle();
});

var lastScrollTop = 0;
$(window).scroll(function () {
    if (window.matchMedia('(min-width: 992px)').matches) {
        if ($(this).scrollTop() > 120) {
            var st = $(this).scrollTop();
            if (st > lastScrollTop){
                $('header').addClass('scroll-down_animation animated').removeClass('scroll-up_animation scroll-up');
                setTimeout(function() {
                    $('header').addClass('scroll-down').removeClass('animated');
                }, 200);
            } else {
                $('header').addClass('scroll-up_animation animated').removeClass('scroll-down_animation scroll-down');
                setTimeout(function() {
                    $('header').addClass('scroll-up').removeClass('animated');
                }, 200);
            }
            lastScrollTop = st;
        }
    }
});

$('.btn_our-works').on('click', '', function () {
    $('.our-objects__hide').slideToggle();
    $(this).remove();
});