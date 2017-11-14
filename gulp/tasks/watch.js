var config		= require( '../util/loadConfig' ).watch;
var gulp		= require( 'gulp' );

// Watch files for changes, recompile/rebuild
gulp.task( 'watch', function() {
	gulp.watch( config.javascript.front, ['uglify:front'] );
	gulp.watch( config.javascript.admin.edit_comments, ['uglify:admin:edit_comments'] );
	gulp.watch( config.javascript.admin.assigned_comments, ['uglify:admin:assigned_comments'] );
	gulp.watch( config.javascript.tinymce, ['uglify:tinymce'] );
	gulp.watch( config.sass.front, ['sass:front'] );
	gulp.watch( config.sass.admin, ['sass:admin'] );
} );