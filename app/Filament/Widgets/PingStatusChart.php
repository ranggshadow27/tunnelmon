<?php

namespace App\Filament\Widgets;

use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use App\Models\PingResult;
use App\Models\DeviceDetail;
use Filament\Forms\Components\Select;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PingStatusChart extends ApexChartWidget
{
    protected static ?string $pollingInterval = '5s';
    protected static ?string $loadingIndicator = 'Loading...';

    public ?string $deviceName = 'scada-pulau-geranting';
    protected $listeners = ['device-name-updated' => 'updateDeviceName'];

    public function updateDeviceName($data): void
    {
        \Illuminate\Support\Facades\Log::info('Received device-name-updated: ' . print_r($data, true));
        $this->deviceName = is_array($data) && isset($data['deviceName']) ? $data['deviceName'] : $this->deviceName;
    }

    protected function getHeading(): null|string|Htmlable|View
    {
        return 'Ping Status for ' . $this->deviceName;
    }

    protected function getOptions(): array
    {
        $start = now()->subDays(2)->setHours(18)->setMinute(5);
        $end = now()->subDays(2)->setHour(18)->setMinute(35);

        // dd($end);

        // Ambil data untuk modem
        $modemDataRaw = PingResult::query()
            ->join('device_details', 'ping_results.ip_address', '=', 'device_details.ip_address')
            ->where('device_details.device_name', $this->deviceName)
            ->where('device_details.device_type', 'modem')
            ->whereBetween('ping_results.created_at', [$start, $end])
            ->select(
                DB::raw('DATE_FORMAT(ping_results.created_at, "%Y-%m-%d %H:%i:00") as time_slot'),
                'ping_results.status'
            )
            ->orderBy('ping_results.created_at')
            ->get();

        // Ambil data untuk router
        $routerDataRaw = PingResult::query()
            ->join('device_details', 'ping_results.ip_address', '=', 'device_details.ip_address')
            ->where('device_details.device_name', $this->deviceName)
            ->where('device_details.device_type', 'router')
            ->whereBetween('ping_results.created_at', [$start, $end])
            ->select(
                DB::raw('DATE_FORMAT(ping_results.created_at, "%Y-%m-%d %H:%i:00") as time_slot'),
                'ping_results.status'
            )
            ->orderBy('ping_results.created_at')
            ->get();

        // Proses data untuk interval 5 menit
        $labels = [];
        $modemData = [];
        $routerData = [];

        // Kelompokkan data per 5 menit
        $modemDataGrouped = $this->groupByFiveMinutes($modemDataRaw, $start, $end);
        $routerDataGrouped = $this->groupByFiveMinutes($routerDataRaw, $start, $end);

        // Gabungkan label dan data
        foreach ($modemDataGrouped as $time => $value) {
            $labels[$time] = $time;
            $modemData[$time] = $value;
        }
        foreach ($routerDataGrouped as $time => $value) {
            $labels[$time] = $time;
            $routerData[$time] = $value;
        }

        $labels = array_values($labels);
        $modemData = array_values($modemData);
        $routerData = array_values($routerData);

        // dd($routerData);

        return [
            'chart' => [
                'type' => "area",
                'height' => 350,
                'fontFamily' => 'inherit',
                'toolbar' => [
                    'autoSelected' => "pan",
                    'tools' => [
                        'download' => true,
                        'selection' => false,
                        'zoom' => false,
                        'zoomin' => false,
                        'pan' => false,
                        'zoomout' => false,
                        'reset' => false,
                    ]
                ],
            ],

            'series' => [
                [
                    'name' => 'Ticket Open',
                    'data' => $modemData,
                ],

                [
                    'name' => 'Closed Ticket',
                    'data' => $routerData,
                ],
            ],

            'legend' => [
                'position' => 'top',
                'fontSize' => '14px',
                'fontWeight' => 400,
                'markers' => [
                    'size' => 4,
                    'offsetX' => -5,
                ],

                'itemMargin' => [
                    'horizontal' => 15,
                    'vertical' => 0,
                ]
            ],

            'xaxis' => [
                'categories' => $labels,
                'type' => 'datetime',
                'labels' => [
                    'style' => [
                        'fontWeight' => 400,
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],

            'yaxis' => [
                'min' => 0,
            ],

            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],

            'grid' => [
                'strokeDashArray' => 10,
                // 'borderColor' => "#B6B6B6FF",
                'position' => 'back',
                // 'clipMarkers' => false,
                'yaxis' => [
                    'lines' => [
                        'show' => true
                    ]
                ],
            ],

            'dataLabels' => [
                'enabled' => true, // Menampilkan nilai di setiap titik
                'offsetY' => -10,
                'style' => [
                    'fontSize' => '12px',
                    'fontWeight' => 'bold',
                ],
                'background' => [
                    'enabled' => false, // Tambahkan background ke label
                    'borderRadius' => 3,
                    'opacity' => 0.7
                ]
            ],

            'markers' => [
                'size' => 4, // Ukuran titik
                // 'colors' => ['#80b918', '#f7b801'], // Sesuaikan warna dengan series
                'strokeWidth' => 2,
                'strokeColors' => '#ffffff', // Warna garis luar
                'hover' => [
                    'size' => 8 // Ukuran saat di-hover
                ]
            ],

            // 'tooltip' => [
            //     'enabled' => true,
            //     'theme' => 'dark',
            // ],

            'fill' => [
                // 'type' => 'gradient',
                'gradient' => [
                    // 'shade' => 'dark',
                    // 'type' => 'horizontal',
                    'enabled' => true,
                    // 'gradientToColors' => ['#ea580c'],
                    // 'inverseColors' => true,
                    'opacityFrom' => 0.55,
                    'opacityTo' => 0.0,
                    // 'stops' => [0, 90, 100],
                ],
            ],
        ];
    }

    protected function groupByFiveMinutes($data, $start, $end): array
    {
        $grouped = [];
        $current = $start->copy()->startOfMinute();
        $end = $end->copy()->endOfMinute();

        // Inisialisasi semua slot waktu dengan nilai 0
        while ($current <= $end) {
            $timeSlot = $current->format('Y-m-d H:i:00');
            $grouped[$timeSlot] = 0; // Default ke 0, bukan null
            $current->addMinutes(5);
        }

        // Isi data berdasarkan status terakhir dalam slot 5 menit
        foreach ($data as $dataPoint) {
            $date = Carbon::parse($dataPoint->time_slot)->startOfMinute();
            // Bulatkan ke slot 5 menit terdekat
            $minute = floor($date->minute / 5) * 5;
            $timeSlot = $date->copy()->startOfHour()->addMinutes($minute)->format('Y-m-d H:i:00');
            $grouped[$timeSlot] = (int) $dataPoint->status; // Ambil status apa adanya (0 atau 1)
        }

        return $grouped;
    }
}
