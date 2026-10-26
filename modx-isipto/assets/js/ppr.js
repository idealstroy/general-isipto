$(document).ready(function() {

    $('.normal.dropdown').dropdown();

    var result = 0,
        resultMultiple = 0,
        resultFirst = 0,
        resultFirstPpr = 0,
        resultMediumAmount = 0;

    $('.type-products-not').on('click' ,function(){
        $('.select-ppr').removeClass('noactive');
        $('.select-pprps .calculate-item').addClass('noactive');
        resultFirst = 0;
    });

    $('.type-products-ppr').on('click' ,function(){
        $('.select-ppr').removeClass('noactive');
        $('.select-industrial').removeClass('noactive');
        $('.select-pprps .calculate-item').addClass('noactive');
        resultFirst = 15000;
    });

    $('.type-products-pprps').on('click' ,function(){
        $('.select-ppr').addClass('noactive');
        $('.select-industrial').removeClass('noactive');
        $('.select-pprps .calculate-item').removeClass('noactive');
        resultFirst = 15000;
        resultFirstPpr = 0;
    });

    $(document).on('input', '.select-pprps input' ,function(){
        amount = Number($('.select-pprps input').val());
        if (resultMediumAmount != 0) {
            if (amount != 0) {
                resultFirst -= resultMediumAmount;
                resultMediumAmount = 5000 * (amount - 1);
                resultFirst += resultMediumAmount;
            }
            else {
                resultMediumAmount = 0;
                resultFirst = 15000;
            }
        }
        else {
            if (amount != 0) {
                resultMediumAmount = 5000 * (amount - 1);
                resultFirst += resultMediumAmount;
            }
            else {
                resultMediumAmount = 0;
                resultFirst = 15000;
            }
        }
    });


    $('.type-products-pprv').on('click' ,function(){
        $('.select-ppr').addClass('noactive');
        $('.select-pprps .calculate-item').addClass('noactive');
        resultFirst = 5000;
        resultFirstPpr = 0;
    });

    $('.type-child0').on('click' ,function(){
        $('.select-industrial').removeClass('noactive');
        resultFirstPpr = 0;
    });

    $('.type-child1').on('click' ,function(){
        $('.select-industrial').addClass('noactive');
        resultFirstPpr = 15000;
        resultFirst = 0;
    });

    $('.type-child2').on('click' ,function(){
        $('.select-industrial').removeClass('noactive');
        resultFirstPpr = 20000;
        resultFirst = 0;
    });

    $('.type-child3').on('click' ,function(){
        $('.select-industrial').removeClass('noactive');
        resultFirstPpr = 25000;
        resultFirst = 0;
    });




    $(document).on('click', '.delete.icon, .calculate-multiple .calculate-item .item' ,function() {
        var th;
        if (resultMultiple != 0) {
            result -= resultMultiple;
            resultMultiple = 0;
        }

        setTimeout(function() {
            th = $('.calculate-multiple').find('.menu').children('div.active');

            th.each(function() {
                var thData = Number($(this).data('price'));

                resultMultiple += thData;
                //console.log('resultMedium:' + resultMultiple);
            });

        }, 20);
    });

    function abc2(n) {
        n += "";
        n = new Array(4 - n.length % 3).join("U") + n;
        return n.replace(/([0-9U]{3})/g, "$1 ").replace(/U/g, "");
    }


    $(document).on('click input', '.select-pprps input, .menu .item, .delete.icon' ,function(){
        setTimeout(function() {
            result = resultMultiple + resultFirst + resultFirstPpr;
            console.log(resultMultiple);
            console.log(resultFirst);
            console.log(resultFirstPpr);
            $('.calculate-result__price').html(abc2(result));
        }, 200);
    });

});