<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;

class EventSeeder extends Seeder
{
    public function run()
    {
        Event::create([
            'title' => 'Jazz Night',
            'description' => "Late Night Jazz offers a tranquil retreat into the velvety embrace of poignant tunes, creating an intimate musical experience that resonates with the peace of the night. This genre flourishes in the quietude, presenting a tapestry woven with calm instrumental compositions and a few dreamy vocal tracks. The channel curates a refined ambiance with its carefully selected repertoire, inviting listeners to unwind and indulge in the seductive allure of slow jazz. Whether you're winding down after a long day or seeking a soothing backdrop for contemplation, Late Night Jazz beckons with its immortal melodies, capturing the essence of nocturnal serenity.",
            'venue' => 'Nairobi Theatre',
            'city' => 'Nairobi',
            'dateTime' => '2025-06-15 19:30:00',
            'imageUrl' => 'events/jazz_night.jpeg',
            'price' => '3000',
            'category_id' => 1,
            'user_id' => 1,
        ]);

        Event::create([
            'title' => 'Afrobeats Party',
            'description' => "Get ready for an electrifying night as we bring you the ultimate Afrobeat experience! From the heart of Africa to the streets of Nairobi, this is where culture, rhythm, and pure energy collide.",
            'venue' => 'The Alchemist',
            'city' => 'Nairobi',
            'dateTime' => '2025-07-01 21:00:00',
            'imageUrl' => 'events/afrobeats.jpeg',
            'price' => '2500',
            'category_id' => 1,
            'user_id' => 1,
        ]);

        Event::create([
            'title' => 'Flutter DevFest',
            'description' => 'All about Flutter and Dart.',
            'venue' => 'iHub Nairobi',
            'city' => 'Nairobi',
            'dateTime' => '2025-08-05 10:00:00',
            'imageUrl' => 'events/flutter_devfest.jpeg',
            'price' => 'Free',
            'category_id' => 2,
            'user_id' => 1,
        ]);

        Event::create([
            'title' => 'Mizizi Padathon Charity Run',
            'description' => "Join thousands of runners from across the globe for the Nairobi Freedom Marathon 2025 — a celebration of endurance, unity, and the unstoppable human spirit.",
            'venue' => 'Waterfront Mall, Karen',
            'city' => 'Nairobi',
            'dateTime' => '2025-06-28 00:00:00',
            'imageUrl' => 'events/marathon.jpeg',
            'price' => '500',
            'category_id' => 3,
            'user_id' => 1,
        ]);
    }
}
