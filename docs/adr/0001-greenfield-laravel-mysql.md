# Greenfield Assessment Portal on Laravel and MySQL

The Assessment Portal is a separate product from BPTI with different users (participants vs. trainees) and different data sensitivity. It will live as a sibling application with its own MySQL schema, not extend BPTI tables or legacy PHP patterns. Laravel was chosen over raw PHP (BPTI), Node, or Python because it provides auth, migrations, roles, and email out of the box while remaining deployable on Connections Counseling's existing PHP-friendly hosting and using MySQL — the team's known database.

**Considered options:** extend BPTI codebase (rejected — wrong domain model); shared database with prefixed tables (rejected — coupling risk); Node/Python stacks (viable but no strong team preference).

**Consequences:** BPTI patterns (access codes, course/survey tables) do not apply. New repo folder, new schema, new deployment path under the parent org.
