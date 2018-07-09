# IcyData Heatmap

## Installation

```composer require icydata/heatmap```

## Usage

```
use IcyData\Heatmap;

$heatmap = new Heatmap();
$heatmap->addPoint(10, 10);
...
$heatmap->addPoint(30, 30);

$heatmap->save('/tmp/heatmap.png');
```

### Use a custom background

```
$heatmap = new Heatmap([
    'backgroundImg' => '/path/to/file.png'
]);
```

## License

Please review LICENSE.md