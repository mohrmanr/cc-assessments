<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $instrumentId = DB::table("instruments")->where("slug", "ecr-r")->value("id");

            if ($instrumentId !== null) {
                $resultIds = DB::table("assessment_results")
                    ->where("instrument_id", $instrumentId)
                    ->pluck("id");

                DB::table("treatment_recommendations")->whereIn("assessment_result_id", $resultIds)->delete();
                DB::table("assessment_results")->where("instrument_id", $instrumentId)->delete();
                DB::table("instruments")->where("id", $instrumentId)->delete();
            }

            foreach (DB::table("treatment_tracks")->get(["id", "reassessment_schedule"]) as $track) {
                $schedule = json_decode((string) $track->reassessment_schedule, true);
                if (! is_array($schedule)) {
                    continue;
                }

                $filtered = array_values(array_filter(
                    $schedule,
                    fn ($entry): bool => ! is_array($entry) || ($entry["instrument_slug"] ?? null) !== "ecr-r"
                ));

                if (count($filtered) !== count($schedule)) {
                    DB::table("treatment_tracks")
                        ->where("id", $track->id)
                        ->update(["reassessment_schedule" => json_encode($filtered)]);
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException("ECR-R results were permanently deleted and cannot be restored by a rollback.");
    }
};
