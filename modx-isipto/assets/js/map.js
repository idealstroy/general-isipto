/*Яндекс-карта*/
{ignore}
ymaps.ready(initMap);

function initMap() {
    var myMap, objects;
    myMap = new ymaps.Map('map', {
        center: [54.736445, 55.995324],
        zoom: 10,
        controls: []
    });
    ymapsTouchScroll(myMap);
    var zoomControl = new ymaps.control.ZoomControl({
        options: {
            size: "small"
        }
    });
    myMap.controls.add(zoomControl);

    objects = ymaps.geoQuery(ymaps.geocode('г. Уфа, ул. Революционная, д. 111/1'))
        .addToMap(myMap);
    objects.then(function () {
        objects.each(function (object) {
            object.options.set('iconLayout', 'default#image');
            object.options.set('iconImageHref', '../assets/css/img/checkpoint.svg');
            object.options.set('iconImageSize', [55, 45]);
            object.options.set('iconImageOffset', [-35, -45]);
        });

        //myMap.behaviors.disable('scrollZoom');


        myMap.setBounds(objects.getBounds(), {
            checkZoomRange: true,
            zoomMargin: [1000, 20, 20, 20]
        }).then(function () {
            myMap.setZoom(16);
        });
    });
}
{/ignore}