## Homebridge aneb HomeKit po domácku

Už od první betaverze iOS10 jsem si říkal, jaká nádhera je aplikace Home od Apple. Ještě jsem ale netušil jak jí oživit, žádné zařízení podporující HomeKit nemám a vzhledem k cenám, jsem si žádné ani neplánoval pořídit, alespoň zatím. Jaké bylo mé překvapení když jsem celkem nedávno narazil na úžasnou aplikaci, přesněji server pro Raspberry Pi. Jako milovník těchto malých počítačů jich mám doma bezpočet a tak jsem se rozhodl jednu použít pro malý experiment o který se s tebou dnes podělím.

Pro účely tohoto článku a pro jeho délku předpokládám, že již máte Raspberry Pi s nainstalovaným systémem Raspbian Jessie a umíte základy správy Linux serverů.

### Základní příprava

Přihlaš se přes SSH na své Raspberry Pi přes aplikaci Terminál, nebo na počítači s Windows přes některý SSH client, například Putty.

Hned na začátku zaktualizuj všechny aplikace a knihovny v systému.
```
sudo apt-get update
sudo apt-get upgrade
```

### Instalace Node.js

Aplikace Homebridge je napsaná v Node.js a stejně tak všechny pluginy, tedy je nutné Node.js v nejnovější verzi nainstalovat.

```
curl deb.nodesource.com/setup_6.x | sudo bash
sudo apt-get install nodejs
```

### Instalace Avahi daemon a dalších potřebných balíčků 

Jako většina služeb Apple potřebuje i Homebridge Avahi pro své fungování, také ho vyžaduje balíček ```mdns``` v knihovně ```HAP_NodeJS```.

```
sudo apt-get install libavahi-compat-libdnssd-dev screen
```

### Instalace Homebridge

```
sudo npm install -g --unsafe-perm homebridge hap-nodejs node-gyp
cd /usr/lib/node_modules/homebridge/
sudo npm install --unsafe-perm bignum
cd /usr/lib/node_modules/hap-nodejs/node_modules/mdns
sudo node-gyp BUILDTYPE=Release rebuild
```

### První spuštění

Po instalaci Homebridge je potřeba jej alespoň jednou spustit, než se pustíš dál. Je potřeba tedy zadáním příkazu ```homebridge```.

Aplikace si nejspíš postěžuje, že nejsou nainstalované žádné pluginy a nebo na chybějící ```config.json```.

### Přidání prvního pluginu a konfigurace

Samotný Homebridge je k ničemu bez jednotlivých pluginů, které přidávají další příslušenství, jsou dostupné mnohé pluginy, například pro IFTTT, HTTP, CLI, a jiné další možné i nemožné. Třeba najdete i plugin pro chytrá zařízení, která již doma máte, ale nepodporují HomeKit.

Všechny pluginy najdete na adrese https://www.npmjs.com/search?q=homebridge

Jako ukázku jsem zvolil plugin pro čtení a zobrazování teploty z HTTP volání, jako datový zdroj používám Forecast.io přesněji DarkSky API, protože nabízí celkem slušný počet požadavků za den, přesně 1000 a to je více než dost pro tyto účely.

První krok je založit si vývojářský účet na DarkSky https://darksky.net/dev/register

Druhý krok je vytvořit si PHP script, který bude číst data z API a vracet je ve formátu, kterému bude rozumět plugin, který pro tyto účely neinstaluješ.

```
mkdir ~/darksky
cd ~/darksky
wget https://raw.githubusercontent.com/tobias-redmann/forecast.io-php-api/master/lib/forecast.io.php
nano index.php
```

Do souboru ```index.php``` vložte následující zdrojový kód:
```php
<?php
include('forecast.io.php');
$api_key = 'ZDE_VLOŽTE_SVŮJ_API_KLÍČ_Z_DARKSKY_API';
$latitude = '50.085403';   //ZDE ZADEJTE POLOHU
$longitude = '14.422086';
$units = 'auto';
$lang = 'cs';
$forecast = new ForecastIO($api_key, $units, $lang);

$condition = $forecast->getCurrentConditions($latitude, $longitude);

$humidity = $condition->getHumidity();
$temperature = $condition->getTemperature();

echo('{"temperature": '.$temperature.',"humidity": '.round((float)$humidity * 100 ).'}');
?>
```

Nyní je nejvyšší čas nainstalovat příslušný plugin, což učiníš následujícím příkazem.

```
sudo npm install -g homebridge-httptemperaturehumidity
```

Poté už jen uprav konfigurační soubor Homebridge

```
cd ~/.homebridge
nano config.json
```
a vlož do něj následující:

```json
{
	"bridge": {
		"name": "RPi-Bridge",
		"username": "DD:EE:3D:E3:CE:30",
		"port": 51826,
		"pin": "007-00-007"
	},

	"description": "RasPi HomeKit Bridge",

	"platforms": [],
	
	"accessories": [{
		"accessory": "HttpTemphum",
	        "name": "Outdoor Weather",
	        "url": "http://localhost:8080?format=json",
	        "sendimmediately": "",
	        "http_method": "GET"
	}]
}
```

### Druhé spuštění

Po úspěšném dokončení všech předchozích kroků by měla následovat čirá radost po druhém spuštění Homebridge. Následujícími dvěma příkazy spustíte službu pro poskytování dat o teplotě a vlhkosti a samotný Homebridge.

```
screen -dmS forecacastio php -S localhost:8080 index.php
homebridge
```

V tuto chvíli, pokud se Homebridge spustí, vypíše kromě jiného kód ve tvaru **XXX-XX-XXX**, který je potřeba pro spárování s aplikací Home v iPhone. Nyní je čas vzít do ruky iPhone nebo iPad a otevřít aplikaci Home a stisknout "Přidat příslušenství", vybrat RPi-Bridge a odklepnout, že chcete zařízení přidat i přesto, že není certifikované MFi od Apple. Kód doporučuji zadat manuálně a následuje už intuitivní nastavení alá Apple. Po skončení budete mít v Homebridge dvě zařízení, jedno ukazující teplotu a druhé ukazující vzdušnou vlhkost.
