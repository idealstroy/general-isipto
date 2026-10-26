(function(w, d, s, h, id) {
    w.roistatProjectId = id;
    w.roistatHost = h;
    var p = d.location.protocol == "https:" ? "https://" : "http://";
    var u = /^.*roistat_visit=[^;]+(.*)?$/.test(d.cookie) ? "/dist/module.js" : "/api/site/1.0/" + id + "/init";
    var js = d.createElement(s);
    js.charset = "UTF-8";
    js.async = 1;
    js.src = p + h + u;
    var js2 = d.getElementsByTagName(s)[0];
    js2.parentNode.insertBefore(js, js2);
})(window, document, 'script', 'cloud.roistat.com', '9eeaab6aa1565e2aebc52d9060fef0f8');

<!-- BEGIN WHATSAPP INTEGRATION WITH ROISTAT -->
if ($(window).width() < '992'){
    (function() {
        if (window.roistat !== undefined) {
            handler();
        } else {
            var pastCallback = typeof window.onRoistatAllModulesLoaded === "function" ? window.onRoistatAllModulesLoaded : null;
            window.onRoistatAllModulesLoaded = function () {
                if (pastCallback !== null) {
                    pastCallback();
                }
                handler();
            };
        }

        function handler() {
            function init() {
                appendMessageToLinks();

                var delays = [1000, 5000, 15000];
                setTimeout(function func(i) {
                    if (i === undefined) {
                        i = 0;
                    }
                    appendMessageToLinks();
                    i++;
                    if (typeof delays[i] !== 'undefined') {
                        setTimeout(func, delays[i], i);
                    }
                }, delays[0]);
            }

            function replaceQueryParam(url, param, value) {
                var explodedUrl = url.split('?');
                var baseUrl = explodedUrl[0] || '';
                var query = '?' + (explodedUrl[1] || '');
                var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
                var queryWithoutParameter = query.replace(regex, "$1").replace(/&$/, '');
                return baseUrl + (queryWithoutParameter.length > 2 ? queryWithoutParameter  + '&' : '?') + (value ? param + "=" + value : '');
            }

            function appendMessageToLinks() {
                var message = document.querySelector('.js-whatsapp-message-container').text;
                var text = message.replace(/{roistat_visit}/g, window.roistatGetCookie('roistat_visit'));
                var linkElements = document.querySelectorAll('[href*="//wa.me"], [href*="//api.whatsapp.com/send"], [href*="//web.whatsapp.com/send"], [href^="whatsapp://send"]');
                for (var elementKey in linkElements) {
                    if (linkElements.hasOwnProperty(elementKey)) {
                        var element = linkElements[elementKey];
                        element.href = replaceQueryParam(element.href, 'text', text);
                    }
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        }
    })();
}
<!-- END WHATSAPP INTEGRATION WITH ROISTAT -->

setTimeout(function () {
    (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)}; m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)}) (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym"); ym(48630242, "init", { id:48630242, clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true });

    (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
    (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

    ym(57607549, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true
    });

    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-118591216-1');
    setTimeout(function(){
        gtag('event', location.pathname, {
            'event_category': 'Без отказов'
        });
    }, 12000);

    !function(){var t=document.createElement("script");t.type="text/javascript",t.async=!0,t.src="https://vk.com/js/api/openapi.js?162",t.onload=function(){VK.Retargeting.Init("VK-RTRG-437462-dGegZ"),VK.Retargeting.Hit()},document.head.appendChild(t)}();

    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1981738315455848');
    fbq('track', 'PageView');
    fbq('track', 'ViewContent');

    var _tmr = window._tmr || (window._tmr = []);
    _tmr.push({id: "3087260", type: "pageView", start: (new Date()).getTime(), pid: "USER_ID"});
    (function (d, w, id) {
        if (d.getElementById(id)) return;
        var ts = d.createElement("script"); ts.type = "text/javascript"; ts.async = true; ts.id = id;
        ts.src = "https://top-fwz1.mail.ru/js/code.js";
        var f = function () {var s = d.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ts, s);};
        if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); }
    })(document, window, "topmailru-code");
}, 3000);

(function(){ document.jivositeloaded=0;var widget_id = 'N7qegDIql6';var d=document;var w=window;function l(){var s = d.createElement('script'); s.type = 'text/javascript'; s.async = true; s.src = '//code.jivosite.com/script/widget/'+widget_id; var ss = document.getElementsByTagName('script')[0]; ss.parentNode.insertBefore(s, ss);}
    function zy(){
        if(w.detachEvent){
            w.detachEvent('onscroll',zy);
            w.detachEvent('onmousemove',zy);
            w.detachEvent('ontouchmove',zy);
            w.detachEvent('onresize',zy);
        }else {
            w.removeEventListener("scroll", zy, false);
            w.removeEventListener("mousemove", zy, false);
            w.removeEventListener("touchmove", zy, false);
            w.removeEventListener("resize", zy, false);
        }
        if(d.readyState=='complete'){l();}else{if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}

        var cookie_date = new Date ( );
        cookie_date.setTime ( cookie_date.getTime()+60*60*28*1000);
        d.cookie = "JivoSiteLoaded=1;path=/;expires=" + cookie_date.toGMTString();
    }
    if (d.cookie.search ( 'JivoSiteLoaded' )<0){
        if(w.attachEvent){
            w.attachEvent('onscroll',zy);
            w.attachEvent('onmousemove',zy);
            w.attachEvent('ontouchmove',zy);
            w.attachEvent('onresize',zy);
        }else {
            w.addEventListener("scroll", zy, {capture: false, passive: true});
            w.addEventListener("mousemove", zy, {capture: false, passive: true});
            w.addEventListener("touchmove", zy, {capture: false, passive: true});
            w.addEventListener("resize", zy, {capture: false, passive: true});
        }
    }else {zy();}
})();