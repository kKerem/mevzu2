const gulp = require('gulp');
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');
const terser = require('gulp-terser');

// style.css dosyasını küçültüp (minify) style.min.css olarak kaydeder
function minifyCSS() {
    return gulp.src('style.css')
        .pipe(cleanCSS({ compatibility: 'ie8' }))
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('.'));
}

// admin_mevzu2.css dosyasını küçültüp (minify) admin_mevzu2.min.css olarak css klasörüne kaydeder
function minifyAdminCSS() {
    return gulp.src('css/admin_mevzu2.css')
        .pipe(cleanCSS({ compatibility: 'ie8' }))
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('css'));
}

// main.js dosyasını küçültüp (minify) main.min.js olarak kaydeder
function minifyJS() {
    return gulp.src('js/main.js')
        .pipe(terser())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('js'));
}

// Tüm dosyalar için izleme görevleri
function watchFiles() {
    gulp.watch('style.css', minifyCSS);
    gulp.watch('css/admin_mevzu2.css', minifyAdminCSS);
    gulp.watch('js/main.js', minifyJS);
}

// CSS İçin Özel İzleme
function watchCSS() {
    gulp.watch('style.css', minifyCSS);
    gulp.watch('css/admin_mevzu2.css', minifyAdminCSS);
}

// Sadece admin_mevzu2.css için izleme
function watchAdminCSS() {
    gulp.watch('css/admin_mevzu2.css', minifyAdminCSS);
}

// JS İçin Özel İzleme
function watchJS() {
    gulp.watch('js/main.js', minifyJS);
}

// Gulp görevlerini dışa aktarıyoruz
exports.minifyCSS = minifyCSS;
exports.minifyAdminCSS = minifyAdminCSS;
exports.minifyJS = minifyJS;
exports.watchJS = watchJS;
exports.watchCSS = watchCSS;
exports.watchAdminCSS = watchAdminCSS;
exports['watch:admin-css'] = watchAdminCSS;
exports['watch:css'] = watchCSS;
exports.watch = watchFiles;
exports.default = gulp.series(gulp.parallel(minifyCSS, minifyAdminCSS, minifyJS), watchFiles);
