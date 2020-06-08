module.exports = function (grunt) {

    const sass = require('node-sass');
    
    require('load-grunt-tasks')(grunt);

    /*@todo minify + watch-mode
    sass: {
     dist: {
                options: {
                    sourceMap,
                    implementation: sass,
                    outputStyle: 'compressed',
                    includePaths: ['<%= global_vars.theme_scss %>', '<%= global_vars.base_theme_path %>/scss/'].concat(bourbon)
                },
                files: cssApp
            },
            dev : {
                options: {
                    sourceMap,
                    implementation: sass,
                    outputStyle: 'expanded',
                    includePaths: ['<%= global_vars.theme_scss %>', '<%= global_vars.base_theme_path %>/scss/'].concat(bourbon)
                },
                files: cssApp
            }
    */

    grunt.initConfig({
        sass: {
            options: {
                implementation: sass,
                sourceMap: true
            },
            dist: {
                files: {
                    'css/main.css': 'sass/main.scss',
                    'css/noscript': 'sass/noscript.scss'
                }
            }
        },
        watch: {
            grunt: {files: ['Gruntfile.js']},

            sass: {
                files: 'sass/**/*.scss',
                tasks: ['sass:dist'],
                options: {
                    livereload: true
                }
            },
        },
    });
    
    grunt.registerTask('default', ['sass']);
}