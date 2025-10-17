<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\HowItWorksSection;
use App\Models\HowItWorksStep;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class HowItWorksController extends BaseController
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'sections' => ['required','array'],
            'sections.*.title' => ['required','string','max:255'],
            'sections.*.steps' => ['required','array','min:1'],
            'sections.*.steps.*' => ['required','string','max:255'],
        ]);

        DB::transaction(function () use ($data) {
            // Replace all content to keep ordering simple
            HowItWorksStep::query()->delete();
            HowItWorksSection::query()->delete();

            foreach (array_values($data['sections']) as $sIndex => $sec) {
                /** @var HowItWorksSection $section */
                $section = HowItWorksSection::create([
                    'title' => trim($sec['title']),
                    'position' => $sIndex,
                ]);
                $steps = array_values($sec['steps']);
                foreach ($steps as $i => $title) {
                    $title = trim((string) $title);
                    if ($title === '') continue;
                    HowItWorksStep::create([
                        'section_id' => $section->id,
                        'title' => $title,
                        'position' => $i,
                    ]);
                }
            }
        });

        return back()->with('status', 'تم تحديث قسم كيف تعمل الخدمة');
    }
}

