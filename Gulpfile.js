var gulp =  require("gulp"),
    shell = require("gulp-shell");

gulp.task('coverage', shell.task('lr-http-server -p 9090 -d build/html/'));
