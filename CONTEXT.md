# Assessment Portal

Laravel application for Connections Counseling. It hosts Clinical Assessment (screening, scales, clinicians) and Course (continuing-education video workflows that replace the legacy BPTI site).

## Language

**Assessment Portal**:
The Laravel web application that hosts Connections Counseling products, currently clinical assessment and continuing-education courses.
_Avoid_: BPTI, the site, Connections Counseling site (parent org is broader)

**Clinical Assessment**:
The product inside the Assessment Portal for eligibility screening, standardized scales, scores, and clinician–participant work.
_Avoid_: Course platform, training site

**Course**:
A continuing-education offering with a gated workflow (access, optional pretest, video, posttest, score, certificate). Distinct from clinical assessment.
_Avoid_: Module, webinar (those are steps), treatment track

**Courses List**:
The Learner home for the Course product: Courses they can purchase or already have Course Access to. Shown in nav when the Account has the Learner role.
_Avoid_: All courses (BPTI page name), catalog (until we need a public shop)

**Course Workflow**:
The six-step page for one Course: Pay, Pretest, Course Video, Posttest, Score, Certificate. Steps enable in order.
_Avoid_: Coursepage, pipeline

**Learner**:
A Role that lets an Account take Courses.
_Avoid_: Student, trainee, participant (clinical Role)

**Role**:
An enabled capability on an Account. An Account may have more than one Role at once (e.g. Learner and Admin). Admins grant and revoke Roles.
_Avoid_: User type, account type

**Course Access**:
Permission for an Account to proceed through a specific Course. Granted by Purchase when that Course requires payment, or by an Admin without payment.
_Avoid_: Enrollment (clinical-adjacent), access code (BPTI mechanism)

**Purchase**:
The Pay step for a Course that requires payment. Completing Purchase grants Course Access for that Course only. A live payment processor is not required for the first Course; a recorded stub Purchase is enough to unlock the workflow.
_Avoid_: Transaction, checkout (until a processor is chosen)

**Participant**:
A person seeking assessment or treatment who may complete screening and scales through the portal.
_Avoid_: User, client, patient (until legal/clinical preference is confirmed)

**Clinician**:
A licensed or authorized provider who reviews participant scores, determines treatment eligibility, and communicates through the portal.
_Avoid_: User, counselor (too broad), admin

**Eligibility Screening**:
The pre-account gate: interpersonal interviewing and criteria checks that determine whether a participant may create an account and proceed to assessments.
_Avoid_: Signup, registration, intake form (too generic)

**Assessment**:
A standardized scale administered to a participant (e.g. PTSD, attachment, dissociation, ACE, self-efficacy), producing a scored result stored with date and reference metadata.
_Avoid_: Quiz, test, survey (BPTI terminology)

**Enrollment Model**:
Hybrid — public eligibility screening first; account creation only after passing eligibility; assessments and treatment follow.
_Avoid_: Self-registration, open signup

**Screening Decision**:
Two-stage eligibility — automated self-screen first; borderline or high-risk cases routed to clinician review before an account is issued.
_Avoid_: Manual-only intake, open registration

**Screening Outcome — Eligible**:
Participant meets criteria (automatically or after clinician approval). An account is created and assessments may begin.
_Avoid_: Registered, signed up

**Screening Outcome — Pending Review**:
Borderline or flagged case awaiting clinician decision. No account until approved or declined.
_Avoid_: Waitlist, on hold

**Screening Outcome — Not Eligible**:
Participant does not meet program criteria. No account is created. They receive referral guidance; screening data is retained until manually deleted by authorized staff.
_Avoid_: Rejected, denied

**Screening Retention**:
Identifiable screening data for ineligible or abandoned screenings is kept until manually deleted by authorized staff — not auto-purged on a fixed schedule.
_Avoid_: Expiration, TTL

**Screening Deletion**:
Removal of identifiable pre-account screening data. Permitted for Clinical Supervisors (from the review queue) and Admins (any record). Line clinicians cannot delete screening data.
_Avoid_: Purge, archive

**Account Activation**:
After eligibility approval, the participant receives a one-time email invite link to set their password and activate their account. Links expire and may be resent.
_Avoid_: Registration, signup

**Safety Flag**:
A high-risk signal during screening (e.g. suicidality) that triggers a clinician alert even when no account is created.
_Avoid_: Emergency, crisis (too broad without definition)

**Assessment Schedule**:
Participants complete multiple assessments: the full intake battery at account start, then repeated administrations of selected instruments (e.g. PTSD, dissociation, self-efficacy) on a treatment timeline.
_Avoid_: One-time quiz

**Pretest**:
An optional Course Quiz before the video. If the Course has a Pretest, submitting it unlocks the video. Score is stored for comparison; a passing Pretest score is not required.
_Avoid_: Survey (SurveyMonkey), baseline assessment

**Posttest**:
The required Course quiz after the video. Completing it produces a Score. Not a clinical Assessment.
_Avoid_: Survey (SurveyMonkey), re-assessment

**Course Quiz**:
A scored question set attached to a Course as Pretest or Posttest. Admins edit it separately from clinical Instruments (questions, choices, correct answers, Pass Mark). Learners take it in the existing survey-style UI. Replaces SurveyMonkey for CE.
_Avoid_: Instrument, Assessment, SurveyMonkey survey

**Pass Mark**:
The minimum Posttest Score that unlocks Certificate. Set per Course. The first Course uses 75%.
_Avoid_: Threshold (clinical treatment term)

**Score**:
The Learner's Posttest result for a Course. Always visible after Posttest. Certificate unlocks only when Score meets the Pass Mark.
_Avoid_: Get Score (button label only), assessment result (clinical)

**Certificate**:
A simple generated PDF for a Course (Learner name, title, date, Score), available only after a passing Score. Artwork can be replaced later. Admins may reset a failed Posttest so the Learner can retake it once more.
_Avoid_: Transcript, CEU file (until credit rules are defined)

**Assessment Battery**:
A grouped set of instruments assigned together, such as the launch intake battery containing PCL-5, DES-II, ACE Questionnaire, GSE, and ECR-R.
_Avoid_: Survey packet, test bundle

**Baseline Assessment**:
The first completed administration of an instrument after eligibility, usually as part of the intake battery. Establishes reference scores and dates for later comparison.
_Avoid_: Intake form, initial survey

**Re-assessment**:
A repeat administration of one or more instruments after baseline. Each re-assessment is stored as a new scored record with its own date and item-level responses.
_Avoid_: Retest, follow-up survey

**Treatment Threshold**:
A per-scale score cutoff that flags a participant for specialized treatment consideration. Thresholds are fixed and instrument-specific; a clinician must confirm before treatment is assigned.
_Avoid_: Passing score, high enough (informal)

**Treatment Recommendation**:
A system-generated flag when one or more assessment scores meet treatment thresholds, pending clinician confirmation.
_Avoid_: Diagnosis, eligibility (screening term)

**Primary Clinician**:
The single assigned clinician responsible for a participant's scores, treatment decisions, and messaging.
_Avoid_: Provider, therapist (until preferred clinical term is confirmed)

**Clinician Assignment**:
Each participant gets one primary clinician, routed by treatment track after threshold review (e.g. PTSD track → credentialed clinicians for that track).
_Avoid_: Caseload pool only, shared inbox

**Message Thread**:
The async conversation between one participant and their primary clinician within the portal.
_Avoid_: Chat, inbox (too generic)

**System Message**:
An automated portal-generated entry in a message thread (e.g. assessment completed, re-assessment due, treatment recommendation pending).
_Avoid_: Notification, alert (those are delivery mechanisms, not the record itself)

**Account**:
An authenticated identity (email and password). Roles on the Account determine which products they can use. Participants still receive an Account only after eligibility approval. For the first Course, Admins create Accounts and enable the Learner Role; public Course signup is later.
_Avoid_: Login; user as a synonym for a single Role

**BPTI Training Platform**:
The legacy PHP continuing-education site (`bpti/`) being replaced by Course in the Assessment Portal. SurveyMonkey was its external pretest/posttest host. It stays live until each Course is migrated; the first Course runs in parallel and does not shut BPTI off.
_Avoid_: The site, Connections Counseling site

**Instrument**:
A specific validated scale used in an assessment, identified by name and version (e.g. PCL-5). Launch set is fixed; additional instruments may be added later without breaking historical results.
_Avoid_: Survey, quiz, form

**Launch Instruments**:
PCL-5 (PTSD), DES-II (dissociation), ACE Questionnaire (adverse childhood experiences), GSE (general self-efficacy), ECR-R (attachment). Subject to clinical team confirmation before build.
_Avoid_: PTSD test, attachment scale (informal)

**Treatment (v1)**:
Coordination hub only — screening, assessments, scores, messaging, and track assignment. Therapy is delivered outside the portal (in person or external telehealth).
_Avoid_: Module (BPTI step)

**Treatment Content (planned)**:
Structured self-help materials (modules, worksheets, psychoeducation) delivered in the portal between clinician messages. Out of scope for v1 launch.
_Avoid_: Training, lessons

**Treatment Track**:
A specialized program path assigned after a confirmed treatment recommendation (e.g. PTSD track), determining which clinicians may be assigned.
_Avoid_: Course, program (too generic)

**Admin**:
Internal operator who provisions clinician accounts and configures treatment thresholds, tracks, and instruments.
_Avoid_: Superuser, staff (too vague)

**Clinical Supervisor**:
Senior clinical role that reviews pending screening cases, safety flags, and treatment recommendations; may view and reassign participants within their team's tracks. Cannot change system configuration.
_Avoid_: Manager, director

**Re-assessment Schedule**:
Timing for repeat administrations of selected instruments, configured per treatment track (e.g. PTSD track: PCL-5 every 4 weeks). Admin sets defaults when defining tracks.
_Avoid_: Reminder, follow-up (those are mechanisms)

**Screening Flow**:
Structured eligibility questionnaire with MI-informed framing — reflective prompts and open-language intro/outro around fixed scorable sections. Automated rules determine pass, pending review, or not eligible.
_Avoid_: Intake interview, chatbot

**Assessment Result**:
A scored administration record for one participant taking one instrument at one point in time, including total/subscale scores, item-level responses, instrument version, administration type (baseline or re-assessment), date, treatment track, assigned clinician, and any threshold flags triggered. Participants may have many assessment results over time.
_Avoid_: Survey response (BPTI terminology)
