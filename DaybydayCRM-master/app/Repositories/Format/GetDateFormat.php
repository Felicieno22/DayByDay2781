<?php

namespace App\Repositories\Format;

use App\Enums\Country;
use App\Models\Setting;

class GetDateFormat
{
    private $format;

    const CACHE_KEY = "country_date_format";
    const DEFAULT_COUNTRY = 'en'; // Valeur par défaut

    public function __construct()
    {
        try {
            $setting = Setting::first();
            if (!$setting) {
                // Si aucun paramètre n'existe, on crée les paramètres par défaut
                \Artisan::call('db:seed', [
                    '--class' => 'SettingsTableSeeder'
                ]);
                $setting = Setting::first();
            }
            
            $countryCode = $setting ? $setting->country : self::DEFAULT_COUNTRY;
            $this->format = Country::fromCode($countryCode)->getFormat();
            cache()->set(self::CACHE_KEY, $this->format);
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser le format par défaut
            $this->format = Country::fromCode(self::DEFAULT_COUNTRY)->getFormat();
            cache()->set(self::CACHE_KEY, $this->format);
        }
    }

    public function getAllDateFormats()
    {
        return [
            'frontend_date' => $this->getFrontendDate(),
            'frontend_time' => $this->getFrontendTime(),
            'carbon_date' => $this->getCarbonDate(),
            'carbon_time' => $this->getCarbonTime(),
            'carbon_full_date_with_text' => $this->getFrontendDate(),
            'carbon_date_with_text' => $this->getFrontendDate(),
            'momentjs_day_and_date_with_text' => $this->getMomentDateWithText(),
            'momentjs_time' => $this->getMomentTime(),
        ];
    }

    public function getFrontendDate()
    {
        return $this->format["frontendDate"];
    }

    public function getFrontendTime()
    {
        return $this->format["frontendTime"];
    }

    public function getCarbonTime()
    {
        return $this->format["carbonTime"];
    }

    public function getCarbonDate()
    {
        return $this->format["carbonDate"];
    }

    public function getCarbonFullDateWithText()
    {
        return $this->format["carbonFullDateWithText"];
    }

    public function getCarbonDateWithText()
    {
        return $this->format["carbonDateWithText"];
    }

    public function getMomentDateWithText()
    {
        return $this->format["momentjsDayAndDateWithText"];
    }

    public function getMomentTime()
    {
        return $this->format["momentJsTime"];
    }
}
