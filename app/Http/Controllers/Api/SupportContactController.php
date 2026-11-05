<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\Request;

class SupportContactController extends Controller
{
    /**
     * Get dynamic support and contact details for mobile apps
     */
    public function getSupportContacts(Request $request)
    {
        try {
            $masterEnabled = Settings::getValue('support_contacts_enabled', '1') === '1';

            $email = Settings::getValue('support_email', 'info@safedest.com');
            $emailEnabled = Settings::getValue('support_email_enabled', '1') === '1';

            $website = Settings::getValue('support_website', 'https://www.safedest.com');
            $websiteEnabled = Settings::getValue('support_website_enabled', '1') === '1';

            $phone = Settings::getValue('support_phone', '+966540587001');
            $phoneEnabled = Settings::getValue('support_phone_enabled', '0') === '1';

            $whatsapp1 = Settings::getValue('support_whatsapp_primary', '+966540587001');
            $whatsapp1Label = Settings::getValue('support_whatsapp_primary_label', 'خدمة العملاء والدعم الفني');
            $whatsapp1Enabled = Settings::getValue('support_whatsapp_primary_enabled', '1') === '1';

            $whatsapp2 = Settings::getValue('support_whatsapp_secondary', '+966560915723');
            $whatsapp2Label = Settings::getValue('support_whatsapp_secondary_label', 'الاستفسارات والعمليات');
            $whatsapp2Enabled = Settings::getValue('support_whatsapp_secondary_enabled', '1') === '1';

            $workingHours = Settings::getValue('support_working_hours', 'متاح على مدار الساعة 24/7');
            $workingHoursEnabled = Settings::getValue('support_working_hours_enabled', '1') === '1';

            $whatsappNumbers = [];
            if (!empty($whatsapp1)) {
                $whatsappNumbers[] = [
                    'id' => 'primary',
                    'phone' => $whatsapp1,
                    'label' => $whatsapp1Label ?: 'الدعم الفني',
                    'enabled' => $whatsapp1Enabled,
                ];
            }
            if (!empty($whatsapp2)) {
                $whatsappNumbers[] = [
                    'id' => 'secondary',
                    'phone' => $whatsapp2,
                    'label' => $whatsapp2Label ?: 'خدمة العملاء',
                    'enabled' => $whatsapp2Enabled,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'enabled' => $masterEnabled,
                    'email' => $email,
                    'email_enabled' => $emailEnabled && !empty($email),
                    'website' => $website,
                    'website_enabled' => $websiteEnabled && !empty($website),
                    'phone' => $phone,
                    'phone_enabled' => $phoneEnabled && !empty($phone),
                    'whatsapp_numbers' => $whatsappNumbers,
                    'working_hours' => $workingHours,
                    'working_hours_enabled' => $workingHoursEnabled && !empty($workingHours),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load support contacts: ' . $e->getMessage(),
                'data' => [
                    'enabled' => true,
                    'email' => 'info@safedest.com',
                    'email_enabled' => true,
                    'website' => 'https://www.safedest.com',
                    'website_enabled' => true,
                    'phone' => '+966540587001',
                    'phone_enabled' => false,
                    'whatsapp_numbers' => [
                        [
                            'id' => 'primary',
                            'phone' => '+966540587001',
                            'label' => 'الدعم الفني',
                            'enabled' => true,
                        ],
                        [
                            'id' => 'secondary',
                            'phone' => '+966560915723',
                            'label' => 'خدمة العملاء',
                            'enabled' => true,
                        ],
                    ],
                    'working_hours' => 'متاح على مدار الساعة 24/7',
                    'working_hours_enabled' => true,
                ],
            ]);
        }
    }
}

