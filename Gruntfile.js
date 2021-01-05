module.exports = function (grunt) {
	grunt.initConfig({
		// Define variables
		pkg: grunt.file.readJSON('package.json'),

		// COPY FILES - Copy needed files from build to trunk
		copy: {
			options: {
				process(content) {
					if (typeof content !== 'string') {
						return content;
					}
					return grunt.template.process(content);
				},
			},
			build: {
				expand: true,
				cwd: 'src',
				src: ['**/*.md', '**/*.txt', '**/*.svg', '**/*.po', '**/*.pot', '**/*.tmpl.html', '**/*.php'],
				dest: 'trunk/',
				filter: 'isFile'
			},
			build_stream: {
				expand: true,
				options: {
					encoding: null
				},
				cwd: 'src',
				src: ['**/*.mo', 'img/**/*', 'fonts/**/*'],
				dest: 'trunk/',
				filter: 'isFile'
			},
		},

		// COMPRESS - Create a zip file from a new trunk
		compress: {
			main: {
				options: {
					archive: 'dist/plugin/<%= pkg.name %>.<%= pkg.version %>.zip',
				},
				files: [
					{
						src: ['**'],
						cwd: 'trunk',
						expand: true,
						dest: '<%= pkg.name %>'
					},
				],
			},
		},
	});

	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-compress');

	grunt.registerTask('release', ['copy', 'compress']);
};
