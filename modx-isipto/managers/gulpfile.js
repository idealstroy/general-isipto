let gulp = require("gulp"),
    stylus = require("gulp-stylus"),
    pug = require("gulp-pug"),
    //browserSync = require("browser-sync"),
    autoprefixer = require("gulp-autoprefixer"),
    sourceMaps = require("gulp-sourcemaps"),
    cleanCSS = require("gulp-cleancss"),
    rename = require("gulp-rename"),
    uglify = require('gulp-uglify-es').default,
    notify = require("gulp-notify"),
    imageMin = require("gulp-imagemin"),
    gulpIf = require("gulp-if"),
    plumber = require("gulp-plumber"),
    nib = require("nib"),
    rupture = require("rupture"),
    del = require("del"),
    notifier = require("node-notifier"),
    concat = require("gulp-concat"),
    argv = require("yargs").argv,
    cheerio = require("gulp-cheerio"),
    svgSprite = require("gulp-svg-sprite"),
    svgMin = require("gulp-svgmin"),
    replace = require("gulp-replace"),
    gcmq = require('gulp-group-css-media-queries');

let path = {
    assets: {
        html: "./assets/pug/pages/**/*.pug",
        css: "./assets/stylus/main.styl",
        js: "./assets/js/common.js",
        svg: "./assets/img/svg/*.svg",
        img: "./assets/img/**/*.+(jpg|jpeg|png|gif|svg|ico)",
        fonts: "./assets/fonts/**/*.+(ttf|eot|woff|svg)"
    },
    dest: {
        html: "./dist/",
        css: "./dist/css/",
        fonts: "./dist/fonts/",
        js: "./dist/js/",
        img: "./dist/img/",
        svg: "./dist/img/"
    },
    libs: {
        css: [
            "./assets/libs/bootstrap4/dist/css/bootstrap-grid.min.css",
            "./assets/libs/normalize.css/normalize.css",
            //"./assets/libs/fancybox/dist/jquery.fancybox.min.css",
            //"./assets/libs/swiper/dist/css/swiper.min.css",
            //"./assets/fonts/stylesheet.css",
            //"./dist/css/common.min.css",
            //"./assets/components/ajaxform/css/default.css",
            //"./assets/components/ajaxform/css/lib/jquery.jgrowl.min.css"
        ],
        js: [
            "./assets/libs/jquery/dist/jquery.min.js",
            //"./assets/libs/fancybox/dist/jquery.fancybox.min.js",
            //"./assets/libs/fancybox/dist/jquery.fancybox.min.js",
            //"./assets/libs/swiper/dist/js/swiper.min.js",
            //"./assets/libs/svg4everybody.min.js",
            //"./dist/js/common.min.js",
            //"./assets/components/ajaxform/js/default.js"
        ]
    },
    watch: {
        html: "./assets/pug/**/*.pug",
        css: "assets/stylus/**/*.styl",
        js: "assets/js/common.js",
        img: "assets/img/**/*.+(jpg|jpeg|png|gif|svg|ico)",
        svg: "assets/img/svg/*.svg",
    }
};

/*gulp.task("browser-sync", function () {
    browserSync({
        server: {
            baseDir: "./dist/"
        },
        notify: false,
        open: false,
        // tunnel: true, tunnel: "project-name",
        // Demonstration page: http://project-name.localtunnel.me
    });
    gulp.watch(path.dest.html).on("change", browserSync.reload);
});*/

gulp.task("html", function () {
    return gulp.src(path.assets.html)
        .pipe(plumber())
        .pipe(pug({
            pretty: true
        }))
        .pipe(gulp.dest(path.dest.html))
        //.pipe(browserSync.stream());
});

gulp.task("styles", function () {
    return gulp.src(path.assets.css)
        //.pipe(plumber())
        //.pipe(sourceMaps.init())
        .pipe(stylus({
            use: [nib(), rupture()]
        }))
        .pipe(autoprefixer(["last 5 versions"]))
        //.pipe(gcmq())
        //.pipe(cleanCSS())
        .pipe(rename({
            basename: "main",
            suffix: ".min",
        }))
        .pipe(sourceMaps.write('.'))
        .pipe(gulp.dest(path.dest.css))
        //.pipe(browserSync.stream());
});

gulp.task("vendorCss", function () {
    return gulp.src(path.libs.css)
        .pipe(concat("vendor.min.css"))
        .pipe(cleanCSS())
        .pipe(gulp.dest(path.dest.css));
});

gulp.task("js", function () {
    return gulp.src(path.assets.js)
        .pipe(plumber())
        .pipe(rename({
            basename: "common"
        }))
        .pipe(uglify())
        .pipe(rename({
            suffix: ".min"
        }))
        .pipe(gulp.dest(path.dest.js))
        //.pipe(browserSync.stream());
});


gulp.task("vendorJs", function() {
    return gulp.src(path.libs.js)
        .pipe(concat("vendor.min.js"))
        .pipe(uglify())
        .pipe(gulp.dest(path.dest.js));
});

gulp.task("svgSprite", function() {
    return gulp.src(path.assets.svg)
        .pipe(svgMin({
            js2svg: {
                pretty: true
            }
        }))
        .pipe(cheerio({
            run: function ($) {
                $("[fill]").removeAttr("fill");
                $("[stroke]").removeAttr("stroke");
                $("[style]").removeAttr("style");
            },
            parserOptions: {
                xmlMode: true
            }
        }))
        .pipe(replace("&gt;", ">"))
        .pipe(svgSprite({
            mode: {
                inline: true,
                symbol: {
                    sprite: "../sprite.svg"
                }
            }
        }))
        .pipe(gulp.dest(path.dest.svg));
});


gulp.task("image-min", function () {
    return gulp.src(path.assets.img)
        .pipe(gulp.dest(path.dest.img).on("error", notify.onError()));
});

gulp.task("fonts", function() {
    return gulp.src(path.assets.fonts)
        .pipe(gulp.dest(path.dest.fonts));
});

gulp.task("watch", function () {
    gulp.watch(path.watch.css, gulp.series("styles"));
    gulp.watch(path.watch.html, gulp.series("html"));
    gulp.watch(path.watch.js, gulp.series("js"));
    gulp.watch(path.watch.img, gulp.series("image-min"));
    gulp.watch(path.watch.svg, gulp.series('svgSprite'));
});


gulp.task("default", gulp.parallel("watch", "image-min", "fonts", "html", "styles", "js", "vendorCss", "vendorJs"));

gulp.task("build", gulp.series(gulp.parallel("html", "styles", "js", "svgSprite", "image-min", "fonts")));

gulp.task('del', function () {
    return del("./dist/**").then(function () {
        notifier.notify({
            message: 'Успешно удалено бро!'
        });
    }).catch(function () {
        notifier.notify({
            message: 'Бро случилась ошибка:c'
        });
    });
});