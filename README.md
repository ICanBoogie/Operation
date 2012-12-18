# Operation

Operations are the doers of the MOVE world. They are responsible for making changes to your models,
and for responding to events triggered by user interactions.





## Requirements

The package requires PHP 5.2 or later. The [icanboogie/http](https://packagist.org/packages/icanboogie/http)
package is also required.





## Installation

The recommended way to install this package is through [composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
    "minimum-stability": "dev",
    "require": {
		"icanboogie/operation": "dev-master"
    }
}
```





### Cloning the repository

The package is [available on GitHub](https://github.com/ICanBoogie/Operation), its repository can
be cloned with the following command line:

	$ git clone git://github.com/ICanBoogie/Operation.git





## Documentation

The package is documented as part of the [ICanBoogie](http://icanboogie.org/) framework
[documentation](http://icanboogie.org/docs/). You can generate the documentation for the package
and its dependencies with the `make doc` command. The documentation is generated in the `docs`
directory. [ApiGen](http://apigen.org/) is required. You can later clean the directory with
the `make clean` command.





## Testing

The test suite is ran with the `make test` command. [Composer](http://getcomposer.org/) is
automatically installed as well as all dependencies required to run the suite. You can later
clean the directory with the `make clean` command.





## License

ICanBoogie/Operation is licensed under the New BSD License - See the LICENSE file for details.