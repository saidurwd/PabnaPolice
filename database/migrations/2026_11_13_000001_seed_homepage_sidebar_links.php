<?php

use App\Support\SiteCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Moves the header badges, partner links and emergency numbers that used to be typed into the templates into the
 * site_links table, so they can be edited under Website > Site links. A group that already has rows is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $img = 'assets/images/';

        $groups = [
            'header_badges' => [
                ['Election Commission', null, 'https://www.police.gov.bd/en/election_information_corner', $img.'election.jpg'],
                ['999 National Emergency Service', null, 'https://www.999.gov.bd/', $img.'999_logo_3.png'],
            ],
            'partner_links' => [
                ['INTERPOL', null, 'https://www.interpol.int/', $img.'background_8290084607.png'],
                ['United Nations(UN)', null, 'https://www.un.org/en/index.html', $img.'background_5460085213.png'],
                ['International Association of Chiefs of Police(IACP)', null, 'http://www.theiacp.org/', $img.'background_1593085257.png'],
            ],
            'emergency' => [
                ['জরুরি সেবা', '৯৯৯', 'https://www.999.gov.bd/', $img.'emergency/e1.png', false],
                ['সরকারি তথ্য ও সেবা', '৩৩৩', 'https://333.gov.bd/', $img.'emergency/e2.webp', false],
                ['ফায়ার সার্ভিস হটলাইন', '১০২', 'https://fireservice.gov.bd/', $img.'emergency/e3.jpg', false],
                ['দুদক', '১০৬', 'https://acc.org.bd/', $img.'emergency/e4.png', true],
                ['দুর্যোগের আগাম বার্তা', '১০৯০', 'https://ddm.gov.bd/', $img.'emergency/e5.png', true],
                ['নারী ও শিশু নির্যাতন প্রতিরোধ', '১০৯', 'https://mowca.gov.bd/', $img.'emergency/e6.png', true],
                ['বিআরটিএ সার্ভিস পোর্টাল (বিএসপি)', '১৬১০৭', 'https://bsp.brta.gov.bd/', $img.'emergency/e7.png', true],
                ['সুপ্রীম কোর্ট হেল্পলাইন', '১০৩', 'https://www.supremecourt.gov.bd/web/', $img.'emergency/e8.jpg', true],
            ],
        ];

        foreach ($groups as $group => $rows) {
            if (DB::table('site_links')->where('group', $group)->exists()) {
                continue;
            }
            foreach ($rows as $i => $row) {
                [$title, $subtitle, $url, $image] = $row;
                $extra = $row[4] ?? false;
                DB::table('site_links')->insert([
                    'group' => $group, 'title' => $title, 'subtitle' => $subtitle, 'url' => $url, 'image' => $image,
                    'is_extra' => $extra, 'ordering' => $i + 1, 'status' => 'Active',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        Cache::forget(SiteCache::SHARED_KEY);
    }

    public function down(): void
    {
        DB::table('site_links')->whereIn('group', ['header_badges', 'partner_links', 'emergency'])->delete();
        Cache::forget(SiteCache::SHARED_KEY);
    }
};
