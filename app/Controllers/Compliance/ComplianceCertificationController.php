<?php

declare(strict_types=1);

namespace App\Controllers\Compliance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Compliance\ComplianceCertificationService;

final class ComplianceCertificationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('compliance.policies.read');

        $svc = new ComplianceCertificationService($this->container);
        $data = $svc->dashboard($request->ip(), $request->header('user-agent'));

        return $this->view('compliance/certifications', [
            'policies' => $data['policies'],
            'controls' => $data['controls'],
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function createPolicy(Request $request)
    {
        $this->authorize('compliance.policies.create');

        $code = trim((string)$request->input('code', ''));
        $title = trim((string)$request->input('title', ''));
        $description = trim((string)$request->input('description', ''));
        $status = trim((string)$request->input('status', 'draft'));
        $version = (int)$request->input('version', 1);
        $ownerUserId = (int)$request->input('owner_user_id', 0);
        $reviewedAt = trim((string)$request->input('reviewed_at', ''));
        $nextReviewAt = trim((string)$request->input('next_review_at', ''));

        try {
            (new ComplianceCertificationService($this->container))->createPolicy(
                $code,
                $title,
                $description === '' ? null : $description,
                $status,
                $version,
                $ownerUserId > 0 ? $ownerUserId : null,
                $reviewedAt === '' ? null : $reviewedAt,
                $nextReviewAt === '' ? null : $nextReviewAt,
                $request->ip(),
                $request->header('user-agent')
            );
            return $this->redirect('/compliance/certifications');
        } catch (\RuntimeException $e) {
            return $this->redirect('/compliance/certifications?error=' . urlencode($e->getMessage()));
        }
    }

    public function updatePolicy(Request $request)
    {
        $this->authorize('compliance.policies.update');

        $id = (int)$request->input('id', 0);
        $title = trim((string)$request->input('title', ''));
        $description = trim((string)$request->input('description', ''));
        $status = trim((string)$request->input('status', ''));
        $version = (int)$request->input('version', 1);
        $ownerUserId = (int)$request->input('owner_user_id', 0);
        $reviewedAt = trim((string)$request->input('reviewed_at', ''));
        $nextReviewAt = trim((string)$request->input('next_review_at', ''));

        if ($id <= 0) {
            return $this->redirect('/compliance/certifications');
        }

        try {
            (new ComplianceCertificationService($this->container))->updatePolicy(
                $id,
                $title,
                $description === '' ? null : $description,
                $status,
                $version,
                $ownerUserId > 0 ? $ownerUserId : null,
                $reviewedAt === '' ? null : $reviewedAt,
                $nextReviewAt === '' ? null : $nextReviewAt,
                $request->ip(),
                $request->header('user-agent')
            );
            return $this->redirect('/compliance/certifications');
        } catch (\RuntimeException $e) {
            return $this->redirect('/compliance/certifications?error=' . urlencode($e->getMessage()));
        }
    }

    public function createControl(Request $request)
    {
        $this->authorize('compliance.controls.create');

        $policyId = (int)$request->input('policy_id', 0);
        $code = trim((string)$request->input('code', ''));
        $title = trim((string)$request->input('title', ''));
        $description = trim((string)$request->input('description', ''));
        $status = trim((string)$request->input('status', 'planned'));
        $ownerUserId = (int)$request->input('owner_user_id', 0);
        $evidenceUrl = trim((string)$request->input('evidence_url', ''));
        $lastTestedAt = trim((string)$request->input('last_tested_at', ''));

        try {
            (new ComplianceCertificationService($this->container))->createControl(
                $policyId > 0 ? $policyId : null,
                $code,
                $title,
                $description === '' ? null : $description,
                $status,
                $ownerUserId > 0 ? $ownerUserId : null,
                $evidenceUrl === '' ? null : $evidenceUrl,
                $lastTestedAt === '' ? null : $lastTestedAt,
                $request->ip(),
                $request->header('user-agent')
            );
            return $this->redirect('/compliance/certifications');
        } catch (\RuntimeException $e) {
            return $this->redirect('/compliance/certifications?error=' . urlencode($e->getMessage()));
        }
    }

    public function updateControl(Request $request)
    {
        $this->authorize('compliance.controls.update');

        $id = (int)$request->input('id', 0);
        $policyId = (int)$request->input('policy_id', 0);
        $title = trim((string)$request->input('title', ''));
        $description = trim((string)$request->input('description', ''));
        $status = trim((string)$request->input('status', ''));
        $ownerUserId = (int)$request->input('owner_user_id', 0);
        $evidenceUrl = trim((string)$request->input('evidence_url', ''));
        $lastTestedAt = trim((string)$request->input('last_tested_at', ''));

        if ($id <= 0) {
            return $this->redirect('/compliance/certifications');
        }

        try {
            (new ComplianceCertificationService($this->container))->updateControl(
                $id,
                $title,
                $description === '' ? null : $description,
                $status,
                $ownerUserId > 0 ? $ownerUserId : null,
                $evidenceUrl === '' ? null : $evidenceUrl,
                $lastTestedAt === '' ? null : $lastTestedAt,
                $policyId > 0 ? $policyId : null,
                $request->ip(),
                $request->header('user-agent')
            );
            return $this->redirect('/compliance/certifications');
        } catch (\RuntimeException $e) {
            return $this->redirect('/compliance/certifications?error=' . urlencode($e->getMessage()));
        }
    }
}
