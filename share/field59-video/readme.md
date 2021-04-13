# Field59 Video

- [Field59 Video](#field59-video)
	- [Dependencies](#dependencies)
	- [Development Instructions](#development-instructions)
		- [Compiling CSS](#compiling-css)

A WordPress integration for Field59 videos.

## Dependencies

## Development Instructions

### Compiling CSS

To compile the SASS files within this plugin run the following command:

```sh
sass --watch styles/sass:styles
```

Remember to commit both the SASS and the CSS changes to the repository. We do not build SASS on deploy; it's only built locally.