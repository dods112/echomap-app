<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Pin;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguard();

        $admin = User::firstOrNew(['email' => 'admin@echomap.test']);
        $admin->forceFill(['name' => 'Alex Kim', 'password' => Hash::make('password'), 'is_admin' => true])->save();

        $maya = User::firstOrNew(['email' => 'maya@echomap.test']);
        $maya->forceFill(['name' => 'Maya Chen', 'password' => Hash::make('password'), 'is_admin' => false])->save();

        $admin->following()->syncWithoutDetaching([$maya->id]);
        $maya->following()->syncWithoutDetaching([$admin->id]);

        if (Pin::count() === 0) {
            $rows = [
                [$maya->id,  'The library steps',          'Campus walkway (sample)',       'We met here every Friday after class. Someone always brought coffee, and somehow we never opened our textbooks.', 'Friendship',  'Public',  9.03950, 126.21600, '2023-09-14 17:00:00'],
                [$admin->id, 'Midnight study break',       'Campus walkway (sample)',       'At midnight we gave up on problem sets, shared a bag of oranges, and watched the whole courtyard go quiet.',        'Campus Life', 'Friends', 9.03958, 126.21618, '2022-04-22 23:50:00'],
                [$admin->id, 'Last day, first promise',    'Campus walkway (sample)',       'We promised to meet on these steps every reunion weekend, no matter how far away life carried us.',                  'Friendship',  'Public',  9.03941, 126.21607, '2021-05-27 16:30:00'],
                [$maya->id,  'A quiet protest',            'Campus grounds (sample)',              'By noon, the whole lawn was filled with handmade signs. It felt like the campus found its voice at once.',          'History',     'Public',  9.03995, 126.21680, '2019-10-08 12:00:00'],
                [$admin->id, 'First snow, no classes',     'Campus grounds, west side (sample)',                'The cancellation email arrived and within minutes the lawn became a snowball arena.',                               'Campus Life', 'Public',  9.03890, 126.21560, '2020-01-18 09:00:00'],
                [$maya->id,  'The long goodbye',           'Campus entrance (sample)',  'We promised we would all be back in a year. It took three, but we kept the promise.',                               'Friendship',  'Private', 9.03910, 126.21700, '2018-05-29 18:00:00'],
            ];

            foreach ($rows as [$uid, $title, $place, $body, $theme, $vis, $lat, $lng, $date]) {
                Pin::create([
                    'user_id' => $uid, 'title' => $title, 'place' => $place, 'body' => $body,
                    'theme' => $theme, 'visibility' => $vis, 'lat' => $lat, 'lng' => $lng,
                    'created_at' => $date, 'updated_at' => $date,
                ]);
            }

            $first = Pin::orderBy('id')->first();
            Comment::create(['pin_id' => $first->id, 'user_id' => $admin->id, 'body' => 'The light here at 5pm is still exactly the same.']);
            Reaction::create(['pin_id' => $first->id, 'user_id' => $admin->id, 'symbol' => '♥']);
            Reaction::create(['pin_id' => $first->id, 'user_id' => $maya->id, 'symbol' => '✦']);
        }

        Model::reguard();
    }
}