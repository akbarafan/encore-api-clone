<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MessageActivity;
use App\Models\Instructor;
use App\Models\Classes;
use Carbon\Carbon;

class MessageActivitySeeder extends Seeder
{
    public function run()
    {
        // Get first instructor
        $instructor = Instructor::first();

        if (!$instructor) {
            $this->command->info('No instructor found. Please create an instructor first.');
            return;
        }

        // Get instructor's classes
        $classes = Classes::where('instructor_id', $instructor->id)->get();

        if ($classes->isEmpty()) {
            $this->command->info('No classes found for instructor. Please create classes first.');
            return;
        }

        $class = $classes->first();

        // Sample activities with different attachment types
        $activities = [
            [
                'title' => '📸 Class Photo Session Today!',
                'message' => 'Here are some beautiful moments from today\'s class. The students were so engaged and enthusiastic! Look at their smiling faces 😊',
                'attachments' => [
                    [
                        'original_name' => 'class_photo_1.jpg',
                        'filename' => 'sample_1.jpg',
                        'path' => 'message-activities/sample_1.jpg',
                        'size' => 1024000,
                        'mime_type' => 'image/jpeg'
                    ],
                    [
                        'original_name' => 'group_activity.jpg',
                        'filename' => 'sample_2.jpg',
                        'path' => 'message-activities/sample_2.jpg',
                        'size' => 856000,
                        'mime_type' => 'image/jpeg'
                    ],
                    [
                        'original_name' => 'students_presentation.jpg',
                        'filename' => 'sample_3.jpg',
                        'path' => 'message-activities/sample_3.jpg',
                        'size' => 945000,
                        'mime_type' => 'image/jpeg'
                    ]
                ]
            ],
            [
                'title' => '🎥 Class Demo Video',
                'message' => 'Today we learned about practical applications. Here\'s a demo video showing the key concepts we covered.',
                'attachments' => [
                    [
                        'original_name' => 'class_demo.mp4',
                        'filename' => 'demo_video.mp4',
                        'path' => 'message-activities/demo_video.mp4',
                        'size' => 15420000,
                        'mime_type' => 'video/mp4'
                    ]
                ]
            ],
            [
                'title' => '📚 Today\'s Materials',
                'message' => 'Here are the study materials and assignments for today\'s lesson. Please review and complete the exercises.',
                'attachments' => [
                    [
                        'original_name' => 'Lesson_Plan_Chapter_5.pdf',
                        'filename' => 'lesson_plan.pdf',
                        'path' => 'message-activities/lesson_plan.pdf',
                        'size' => 2340000,
                        'mime_type' => 'application/pdf'
                    ],
                    [
                        'original_name' => 'Assignment_Questions.docx',
                        'filename' => 'assignment.docx',
                        'path' => 'message-activities/assignment.docx',
                        'size' => 567000,
                        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ],
                    [
                        'original_name' => 'Data_Analysis_Template.xlsx',
                        'filename' => 'data_template.xlsx',
                        'path' => 'message-activities/data_template.xlsx',
                        'size' => 123000,
                        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ]
                ]
            ],
            [
                'title' => '🏆 Student Achievement',
                'message' => 'Congratulations to our top performers today! Amazing work everyone! 👏',
                'attachments' => [
                    [
                        'original_name' => 'achievement_photo.png',
                        'filename' => 'achievement.png',
                        'path' => 'message-activities/achievement.png',
                        'size' => 789000,
                        'mime_type' => 'image/png'
                    ]
                ]
            ],
            [
                'title' => 'Quick Reminder',
                'message' => 'Don\'t forget about tomorrow\'s presentation! Make sure to prepare your materials. Good luck! 💪',
                'attachments' => null
            ]
        ];

        foreach ($activities as $index => $activityData) {
            MessageActivity::create([
                'instructor_id' => $instructor->id,
                'class_id' => $class->id,
                'title' => $activityData['title'],
                'message' => $activityData['message'],
                'attachments' => $activityData['attachments'],
                'activity_date' => Carbon::today()->subDays(rand(0, 7)),
                'is_pinned' => $index < 2, // Pin first 2 activities
                'is_active' => true,
                'created_at' => Carbon::now()->subDays(rand(0, 5)),
                'updated_at' => Carbon::now()->subDays(rand(0, 3))
            ]);
        }

        $this->command->info('Sample message activities created successfully!');
        $this->command->info('Note: Attachment files are sample data - actual files may not exist in storage.');
    }
}
