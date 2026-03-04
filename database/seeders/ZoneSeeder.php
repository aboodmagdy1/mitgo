<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            [
                'name' => 'Riyadh Central',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 24.74, "lng" => 46.65],
                    "1" => ["lat" => 24.76, "lng" => 46.65],
                    "2" => ["lat" => 24.76, "lng" => 46.7],
                    "3" => ["lat" => 24.74, "lng" => 46.7],
                    "4" => ["lat" => 24.74, "lng" => 46.65],
                    "lat" => 24.7747939755704,
                    "lng" => 46.23046875,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[40.341797, 27.20593], [47.548828, 25.553603], [47.329102, 22.465152], [40.693359, 22.302723], [40.12207, 25.830624], [40.341797, 27.20593]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Riyadh North', 
                'status' => true,
                'points' => [
                    "0" => ["lat" => 24.7400, "lng" => 46.6500],
                    "1" => ["lat" => 24.7600, "lng" => 46.6500],
                    "2" => ["lat" => 24.7600, "lng" => 46.7000],
                    "3" => ["lat" => 24.7400, "lng" => 46.7000],
                    "4" => ["lat" => 24.7400, "lng" => 46.6500],
                    "lat" => 24.75,
                    "lng" => 46.675,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[46.6500, 24.7400], [46.6500, 24.7600], [46.7000, 24.7600], [46.7000, 24.7400], [46.6500, 24.7400]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Riyadh South',
                'status' => true, 
                'points' => [
                    "0" => ["lat" => 24.6800, "lng" => 46.6500],
                    "1" => ["lat" => 24.7000, "lng" => 46.6500],
                    "2" => ["lat" => 24.7000, "lng" => 46.7000],
                    "3" => ["lat" => 24.6800, "lng" => 46.7000],
                    "4" => ["lat" => 24.6800, "lng" => 46.6500],
                    "lat" => 24.69,
                    "lng" => 46.675,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[46.6500, 24.6800], [46.6500, 24.7000], [46.7000, 24.7000], [46.7000, 24.6800], [46.6500, 24.6800]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Riyadh East',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 24.7000, "lng" => 46.7200],
                    "1" => ["lat" => 24.7200, "lng" => 46.7200],
                    "2" => ["lat" => 24.7200, "lng" => 46.7700],
                    "3" => ["lat" => 24.7000, "lng" => 46.7700],
                    "4" => ["lat" => 24.7000, "lng" => 46.7200],
                    "lat" => 24.71,
                    "lng" => 46.745,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[46.7200, 24.7000], [46.7200, 24.7200], [46.7700, 24.7200], [46.7700, 24.7000], [46.7200, 24.7000]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Riyadh West',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 24.7000, "lng" => 46.6000],
                    "1" => ["lat" => 24.7200, "lng" => 46.6000],
                    "2" => ["lat" => 24.7200, "lng" => 46.6400],
                    "3" => ["lat" => 24.7000, "lng" => 46.6400],
                    "4" => ["lat" => 24.7000, "lng" => 46.6000],
                    "lat" => 24.71,
                    "lng" => 46.62,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[46.6000, 24.7000], [46.6000, 24.7200], [46.6400, 24.7200], [46.6400, 24.7000], [46.6000, 24.7000]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Jeddah Central',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 21.5433, "lng" => 39.1728],
                    "1" => ["lat" => 21.5533, "lng" => 39.1728],
                    "2" => ["lat" => 21.5533, "lng" => 39.1828],
                    "3" => ["lat" => 21.5433, "lng" => 39.1828],
                    "4" => ["lat" => 21.5433, "lng" => 39.1728],
                    "lat" => 21.5483,
                    "lng" => 39.1778,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[39.1728, 21.5433], [39.1728, 21.5533], [39.1828, 21.5533], [39.1828, 21.5433], [39.1728, 21.5433]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Jeddah North',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 21.5700, "lng" => 39.1500],
                    "1" => ["lat" => 21.5900, "lng" => 39.1500],
                    "2" => ["lat" => 21.5900, "lng" => 39.2000],
                    "3" => ["lat" => 21.5700, "lng" => 39.2000],
                    "4" => ["lat" => 21.5700, "lng" => 39.1500],
                    "lat" => 21.58,
                    "lng" => 39.175,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[39.1500, 21.5700], [39.1500, 21.5900], [39.2000, 21.5900], [39.2000, 21.5700], [39.1500, 21.5700]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Jeddah South',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 21.5000, "lng" => 39.1500],
                    "1" => ["lat" => 21.5200, "lng" => 39.1500],
                    "2" => ["lat" => 21.5200, "lng" => 39.2000],
                    "3" => ["lat" => 21.5000, "lng" => 39.2000],
                    "4" => ["lat" => 21.5000, "lng" => 39.1500],
                    "lat" => 21.51,
                    "lng" => 39.175,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[39.1500, 21.5000], [39.1500, 21.5200], [39.2000, 21.5200], [39.2000, 21.5000], [39.1500, 21.5000]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Dammam Central',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 26.4207, "lng" => 50.0888],
                    "1" => ["lat" => 26.4307, "lng" => 50.0888],
                    "2" => ["lat" => 26.4307, "lng" => 50.0988],
                    "3" => ["lat" => 26.4207, "lng" => 50.0988],
                    "4" => ["lat" => 26.4207, "lng" => 50.0888],
                    "lat" => 26.4257,
                    "lng" => 50.0938,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[50.0888, 26.4207], [50.0888, 26.4307], [50.0988, 26.4307], [50.0988, 26.4207], [50.0888, 26.4207]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Khobar Area',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 26.2172, "lng" => 50.1971],
                    "1" => ["lat" => 26.2272, "lng" => 50.1971],
                    "2" => ["lat" => 26.2272, "lng" => 50.2071],
                    "3" => ["lat" => 26.2172, "lng" => 50.2071],
                    "4" => ["lat" => 26.2172, "lng" => 50.1971],
                    "lat" => 26.2222,
                    "lng" => 50.2021,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[50.1971, 26.2172], [50.1971, 26.2272], [50.2071, 26.2272], [50.2071, 26.2172], [50.1971, 26.2172]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'King Fahd Airport Area',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 26.4714, "lng" => 49.7976],
                    "1" => ["lat" => 26.4814, "lng" => 49.7976],
                    "2" => ["lat" => 26.4814, "lng" => 49.8076],
                    "3" => ["lat" => 26.4714, "lng" => 49.8076],
                    "4" => ["lat" => 26.4714, "lng" => 49.7976],
                    "lat" => 26.4764,
                    "lng" => 49.8026,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[49.7976, 26.4714], [49.7976, 26.4814], [49.8076, 26.4814], [49.8076, 26.4714], [49.7976, 26.4714]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Mecca Holy Area',
                'status' => true,
                'points' => [
                    "0" => ["lat" => 21.4225, "lng" => 39.8262],
                    "1" => ["lat" => 21.4325, "lng" => 39.8262],
                    "2" => ["lat" => 21.4325, "lng" => 39.8362],
                    "3" => ["lat" => 21.4225, "lng" => 39.8362],
                    "4" => ["lat" => 21.4225, "lng" => 39.8262],
                    "lat" => 21.4275,
                    "lng" => 39.8312,
                    "geojson" => [
                        "type" => "FeatureCollection",
                        "features" => [
                            [
                                "type" => "Feature",
                                "geometry" => [
                                    "type" => "Polygon",
                                    "coordinates" => [[[39.8262, 21.4225], [39.8262, 21.4325], [39.8362, 21.4325], [39.8362, 21.4225], [39.8262, 21.4225]]]
                                ],
                                "properties" => []
                            ]
                        ]
                    ]
                ]
            ],
        ];

        foreach ($zones as $zone) {
            Zone::create($zone);
        }

        $this->command->info('Zones seeded successfully!');
    }
}
