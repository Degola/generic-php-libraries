# Groar generic php libraries

## Version

v1

## Configuration

* Api documentation
https://srcdocs.honeytracks.net/Groar/generic-php-libraries/phpdoc/classes/Groar.Generic.Configuration.html

### Examples

```php5
$config = \Groar\Generic\Configuration::Factory(
	\Groar\Generic\Configuration\Driver_Etcd::Factory([
		"hosts" => explode(",", getenv("ETCD_HOST"))
	])
);
$values = $config->get('/');
```