!function(window){
    var $q = function(q, res){
            if (document.querySelectorAll) {
                res = document.querySelectorAll(q);
            } else {
                var d=document
                    , a=d.styleSheets[0] || d.createStyleSheet();
                a.addRule(q,'f:b');
                for(var l=d.all,b=0,c=[],f=l.length;b<f;b++)
                    l[b].currentStyle.f && c.push(l[b]);

                a.removeRule(0);
                res = c;
            }
            return res;
        }
        , addEventListener = function(evt, fn){
            window.addEventListener
                ? this.addEventListener(evt, fn, false)
                : (window.attachEvent)
                ? this.attachEvent('on' + evt, fn)
                : this['on' + evt] = fn;
        }
        , _has = function(obj, key) {
            return Object.prototype.hasOwnProperty.call(obj, key);
        }
    ;

    function loadImage (el, fn) {
        var img = new Image()
            , src = el.getAttribute('data-src');
        img.onload = function() {
            if (!! el.parent)
                el.parent.replaceChild(img, el)
            else
                el.src = src;

            fn? fn() : null;
        }
        img.src = src;
    }

    function elementInViewport(el) {
        var rect = el.getBoundingClientRect()

        return (
            el.getBoundingClientRect().top <= window.innerHeight+400 && el.getBoundingClientRect().bottom >= 0 && getComputedStyle(el).display !== "none"
        )
    }

    var images = new Array()
        , query = $q('img.image-load')
        , processScroll = function(){
            for (var i = 0; i < images.length; i++) {
                if (elementInViewport(images[i])) {
                    loadImage(images[i], function () {
                        images.splice(i, i);
                    });
                }
            };
        }
    ;
    // Array.prototype.slice.call is not callable under our lovely IE8
    for (var i = 0; i < query.length; i++) {
        images.push(query[i]);
    };

    processScroll();
    addEventListener('scroll',processScroll);

}(this);

document.addEventListener("DOMContentLoaded", function() {
    var lazyBackgrounds = [].slice.call(document.querySelectorAll(".image-bg"));

    if ("IntersectionObserver" in window) {
        let lazyBackgroundObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {entries
                    entry.target.classList.add("visible");
                    lazyBackgroundObserver.unobserve(entry.target);
                }
            });
        },{
            rootMargin: "0px 0px 400px 0px"
        });

        lazyBackgrounds.forEach(function(lazyBackground) {
            lazyBackgroundObserver.observe(lazyBackground);
        });
    }
});