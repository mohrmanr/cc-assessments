<?php

use App\Models\Instrument;
use App\Support\AttachmentSurvey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $instrument = Instrument::query()->where("slug", "ecr-rs")->first();
        if (! $instrument) {
            return;
        }

        $items = AttachmentSurvey::withoutRetiredTargets($instrument->items ?? []);
        $hasGeneral = collect($items)->contains(
            fn (array $item): bool => ($item["target"] ?? "") === "general"
        );
        if (! $hasGeneral) {
            $items = array_merge(
                AttachmentSurvey::expandForTargets(AttachmentSurvey::ecrRsBaseItems(), ["general"]),
                $items
            );
        }

        $order = array_flip(array_keys(AttachmentSurvey::TARGETS));
        usort($items, function (array $a, array $b) use ($order): int {
            return ($order[$a["target"] ?? ""] ?? 99) <=> ($order[$b["target"] ?? ""] ?? 99);
        });

        $config = $instrument->scoring_config ?? [];
        $config["repeat_for_targets"] = ["general", "mother", "father"];
        $description = (string) ($config["description"] ?? "");
        if ($description === "" || str_contains($description, "partner") || str_contains($description, "best friend")) {
            $config["description"] = "Rate how you experience close relationships in general, and with your mother and your father.";
        }
        $instructions = (string) ($config["instructions"] ?? "");
        if ($instructions === "" || str_contains($instructions, "four times") || str_contains($instructions, "partner") || str_contains($instructions, "best friend")) {
            $config["instructions"] = "You will answer the same set of questions three times: once for close relationships in general, once for your mother, and once for your father. For each statement, select how much you agree or disagree.";
        }

        $instrument->update([
            "items" => $items,
            "scoring_config" => $config,
        ]);
    }

    public function down(): void
    {
        // Partner and best-friend questions are not restored.
    }
};
