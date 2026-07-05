# Assessment Portal

Clinical assessment portal under Connections Counseling. Participants complete eligibility screening before receiving an account, then take standardized scales whose scores clinicians use to guide specialized treatment.

## Language

**Assessment Portal**:
The new web application for pre-account screening, standardized assessments, score storage, and clinician–participant communication.
_Avoid_: BPTI, training site, course platform

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
_Avoid_: One-time quiz, pretest/posttest (BPTI terminology)

**Assessment Battery**:
A grouped set of instruments assigned together, such as the launch intake battery containing PCL-5, DES-II, ACE Questionnaire, GSE-10, and ECR-R.
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
A participant or clinician identity with email-and-password authentication, created only after eligibility approval (participants) or admin provisioning (clinicians).
_Avoid_: User (BPTI term), login

**BPTI Training Platform**:
The existing continuing-education product for social-services professionals. A separate product from the Assessment Portal — different people, different purpose.
_Avoid_: The site, Connections Counseling site (parent org is broader)

**Instrument**:
A specific validated scale used in an assessment, identified by name and version (e.g. PCL-5). Launch set is fixed; additional instruments may be added later without breaking historical results.
_Avoid_: Survey, quiz, form

**Launch Instruments**:
PCL-5 (PTSD), DES-II (dissociation), ACE Questionnaire (adverse childhood experiences), GSE-10 (general self-efficacy), ECR-R (attachment). Subject to clinical team confirmation before build.
_Avoid_: PTSD test, attachment scale (informal)

**Treatment (v1)**:
Coordination hub only — screening, assessments, scores, messaging, and track assignment. Therapy is delivered outside the portal (in person or external telehealth).
_Avoid_: Course, module (BPTI terminology)

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
