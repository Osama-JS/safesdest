<?php

namespace App\Imports;

use App\Models\Company_End_Client;
use App\Models\Company_Province;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompanyEndClientsImport implements ToCollection, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    protected $companyId;
    protected $provinceCache = [];

    /**
     * Static English → Arabic city name translation map (Saudi Arabia & GCC)
     */
    protected static array $cityTranslations = [
        // Major Saudi Cities
        'riyadh'          => 'الرياض',
        'jeddah'          => 'جدة',
        'jedda'           => 'جدة',
        'jidda'           => 'جدة',
        'makkah'          => 'مكة المكرمة',
        'mecca'           => 'مكة المكرمة',
        'madinah'         => 'المدينة المنورة',
        'medina'          => 'المدينة المنورة',
        'al madinah'      => 'المدينة المنورة',
        'al-madinah'      => 'المدينة المنورة',
        'dammam'          => 'الدمام',
        'khobar'          => 'الخبر',
        'al khobar'       => 'الخبر',
        'al-khobar'       => 'الخبر',
        'dhahran'         => 'الظهران',
        'al dhahran'      => 'الظهران',
        'jubail'          => 'الجبيل',
        'al jubail'       => 'الجبيل',
        'yanbu'           => 'ينبع',
        'taif'            => 'الطائف',
        'al taif'         => 'الطائف',
        'tabuk'           => 'تبوك',
        'abha'            => 'أبها',
        'khamis mushait'  => 'خميس مشيط',
        'khamis mushayt'  => 'خميس مشيط',
        'hail'            => 'حائل',
        'ha\'il'          => 'حائل',
        'al hail'         => 'حائل',
        'najran'          => 'نجران',
        'jizan'           => 'جازان',
        'jazan'           => 'جازان',
        'sakaka'          => 'سكاكا',
        'al jouf'         => 'الجوف',
        'jouf'            => 'الجوف',
        'al-jouf'         => 'الجوف',
        'buraydah'        => 'بريدة',
        'buraidah'        => 'بريدة',
        'unaizah'         => 'عنيزة',
        'onaizah'         => 'عنيزة',
        'al qassim'       => 'القصيم',
        'qassim'          => 'القصيم',
        'al-qassim'       => 'القصيم',
        'hafr al batin'   => 'حفر الباطن',
        'hafr albatin'    => 'حفر الباطن',
        'al kharj'        => 'الخرج',
        'al-kharj'        => 'الخرج',
        'kharj'           => 'الخرج',
        'al majmaah'      => 'المجمعة',
        'majmaah'         => 'المجمعة',
        'al qatif'        => 'القطيف',
        'qatif'           => 'القطيف',
        'al ahsa'         => 'الأحساء',
        'ahsa'            => 'الأحساء',
        'hofuf'           => 'الهفوف',
        'al hofuf'        => 'الهفوف',
        'mubarraz'        => 'المبرز',
        'al mubarraz'     => 'المبرز',
        'dawadmi'         => 'الدوادمي',
        'al dawadmi'      => 'الدوادمي',
        'wadi dawasir'    => 'وادي الدواسر',
        'al wajh'         => 'الوجه',
        'wajh'            => 'الوجه',
        'umluj'           => 'أملج',
        'turaif'          => 'طريف',
        'arar'            => 'عرعر',
        'ar ar'           => 'عرعر',
        'sharurah'        => 'شرورة',
        'samtah'          => 'صامطة',
        'sabya'           => 'صبيا',
        'baish'           => 'بيش',
        'al lith'         => 'الليث',
        'lith'            => 'الليث',
        'al qunfudhah'    => 'القنفذة',
        'qunfudhah'       => 'القنفذة',
        'baha'            => 'الباحة',
        'al baha'         => 'الباحة',
        'al-baha'         => 'الباحة',
        'baljurashi'      => 'بلجرشي',
        'al ula'          => 'العلا',
        'ula'             => 'العلا',
        'al-ula'          => 'العلا',
        'khaybar'         => 'خيبر',
        'tayma'           => 'تيماء',
        'rabigh'          => 'رابغ',
        'jeddah - rabigh' => 'رابغ',
        'masadir'         => 'مصادر',
        'mursalat'        => 'المرسلات',
        'qurrayat'        => 'القريات',
        'al qurrayat'     => 'القريات',
        // GCC Countries
        'dubai'           => 'دبي',
        'abu dhabi'       => 'أبوظبي',
        'sharjah'         => 'الشارقة',
        'kuwait city'     => 'مدينة الكويت',
        'kuwait'          => 'الكويت',
        'manama'          => 'المنامة',
        'bahrain'         => 'البحرين',
        'muscat'          => 'مسقط',
        'oman'            => 'سلطنة عُمان',
        'doha'            => 'الدوحة',
        'qatar'           => 'قطر',
    ];

    public function __construct($companyId)
    {
        $this->companyId = $companyId;

        // Pre-load all provinces into memory
        foreach (Company_Province::all() as $province) {
            $this->setProvinceCache($province);
        }
    }

    private function cacheKey(string $name): string
    {
        return strtolower(trim($name));
    }

    private function setProvinceCache(Company_Province $province): void
    {
        if ($province->name_ar) {
            $this->provinceCache[$this->cacheKey($province->name_ar)] = $province->id;
        }
        if ($province->name_en) {
            $this->provinceCache[$this->cacheKey($province->name_en)] = $province->id;
        }
    }

    /**
     * Translate a city name to Arabic using the static map.
     * Returns the Arabic name if found, otherwise returns original.
     */
    private function translateCity(string $city): string
    {
        return static::$cityTranslations[$this->cacheKey($city)] ?? $city;
    }

    private function resolveProvinceId(?string $city): ?int
    {
        if (empty($city)) {
            return null;
        }

        // Try original English name first
        $keyEn = $this->cacheKey($city);
        if (isset($this->provinceCache[$keyEn])) {
            return $this->provinceCache[$keyEn];
        }

        // Translate to Arabic and try again
        $cityAr = $this->translateCity($city);
        $keyAr  = $this->cacheKey($cityAr);
        if (isset($this->provinceCache[$keyAr])) {
            return $this->provinceCache[$keyAr];
        }

        // Not found — create new province with proper Arabic + English names
        $province = Company_Province::create([
            'name_ar'   => $cityAr,                               // Arabic (translated or original)
            'name_en'   => ucwords(strtolower(trim($city))),      // English (formatted)
            'region'    => null,
            'is_active' => true,
        ]);

        $this->setProvinceCache($province);
        return $province->id;
    }

    public function collection(Collection $rows)
    {
        $now        = Carbon::now()->toDateTimeString();
        $toInsert   = [];
        $clientCodes = [];
        $rowsByCode  = [];

        foreach ($rows as $row) {
            $customerName = trim($row['customer_name'] ?? '');
            if (empty($customerName)) continue;

            $clientCode = isset($row['id']) && $row['id'] !== '' && $row['id'] !== null
                ? (string) $row['id']
                : null;

            $data = [
                'company_id'  => $this->companyId,
                'province_id' => $this->resolveProvinceId($row['city'] ?? null),
                'client_code' => $clientCode,
                'name'        => $customerName,
                'phone'       => isset($row['phone']) && $row['phone'] !== '' ? (string) $row['phone'] : null,
                'phone_2'     => null,
                'city'        => isset($row['city']) ? ucwords(strtolower(trim($row['city']))) : null,
                'address'     => null,
                'latitude'    => is_numeric($row['latitude']  ?? null) ? (float) $row['latitude']  : null,
                'longitude'   => is_numeric($row['longitude'] ?? null) ? (float) $row['longitude'] : null,
                'notes'       => isset($row['notes']) && $row['notes'] !== '' ? (string) $row['notes'] : null,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            if ($clientCode !== null) {
                $clientCodes[]           = $clientCode;
                $rowsByCode[$clientCode] = $data;
            } else {
                $toInsert[] = $data;
            }
        }

        // ── Rows WITH client_code ─────────────────────────────────────────
        if (!empty($clientCodes)) {
            $existing = DB::table('company_end_clients')
                ->where('company_id', $this->companyId)
                ->whereIn('client_code', $clientCodes)
                ->pluck('id', 'client_code')
                ->toArray();

            $toUpdate = [];
            $toCreate = [];

            foreach ($rowsByCode as $code => $data) {
                if (isset($existing[$code])) {
                    $toUpdate[$existing[$code]] = $data;
                } else {
                    $toCreate[] = $data;
                }
            }

            foreach (array_chunk($toUpdate, 500, true) as $chunk) {
                foreach ($chunk as $id => $data) {
                    unset($data['created_at']);
                    DB::table('company_end_clients')->where('id', $id)->update($data);
                }
            }

            foreach (array_chunk($toCreate, 500) as $chunk) {
                DB::table('company_end_clients')->insert($chunk);
            }
        }

        // ── Rows WITHOUT client_code (always insert) ──────────────────────
        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('company_end_clients')->insert($chunk);
            }
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
