import { randomUUID } from 'crypto';

export type SubmissionStatus =
  | 'pending'
  | 'verifying'
  | 'passed'
  | 'failed'
  | 'awaiting_approval'
  | 'approved'
  | 'rejected';

export interface SubmissionVerification {
  healthChecksPassed: boolean;
  logScanPassed: boolean;
  visionDiffRatio: number;
  visionDiffThreshold: number;
}

export interface Submission {
  id: string;
  jobId: string;
  helperId: string;
  artifactUri: string;
  notes?: string;
  status: SubmissionStatus;
  verification?: SubmissionVerification;
  createdAt: string;
  updatedAt: string;
}

export interface CreateSubmissionInput {
  jobId: string;
  helperId: string;
  artifactUri: string;
  notes?: string;
}

export interface UpdateSubmissionInput {
  artifactUri?: string;
  notes?: string;
}

const submissions = new Map<string, Submission>();

const nowIso = (): string => new Date().toISOString();

export const createSubmission = (input: CreateSubmissionInput): Submission => {
  const timestamp = nowIso();
  const submission: Submission = {
    id: randomUUID(),
    jobId: input.jobId,
    helperId: input.helperId,
    artifactUri: input.artifactUri,
    notes: input.notes,
    status: 'pending',
    createdAt: timestamp,
    updatedAt: timestamp,
  };
  submissions.set(submission.id, submission);
  return submission;
};

export const listSubmissions = (filter?: Partial<Pick<Submission, 'jobId' | 'helperId' | 'status'>>): Submission[] => {
  const values = Array.from(submissions.values());
  if (!filter) {
    return values;
  }
  return values.filter((submission) => {
    if (filter.jobId && submission.jobId !== filter.jobId) {
      return false;
    }
    if (filter.helperId && submission.helperId !== filter.helperId) {
      return false;
    }
    if (filter.status && submission.status !== filter.status) {
      return false;
    }
    return true;
  });
};

export const getSubmission = (submissionId: string): Submission | undefined => submissions.get(submissionId);

export const requireSubmission = (submissionId: string): Submission => {
  const submission = getSubmission(submissionId);
  if (!submission) {
    throw new Error(`Submission ${submissionId} not found`);
  }
  return submission;
};

export const updateSubmission = (submissionId: string, updates: UpdateSubmissionInput): Submission => {
  const submission = requireSubmission(submissionId);
  const updated: Submission = {
    ...submission,
    ...updates,
    updatedAt: nowIso(),
  };
  submissions.set(submissionId, updated);
  return updated;
};

export const startSubmissionVerification = (submissionId: string): Submission => {
  const submission = requireSubmission(submissionId);
  const updated: Submission = {
    ...submission,
    status: 'verifying',
    updatedAt: nowIso(),
  };
  submissions.set(submissionId, updated);
  return updated;
};

export const recordSubmissionVerification = (
  submissionId: string,
  verification: SubmissionVerification,
): Submission => {
  const submission = requireSubmission(submissionId);
  const status: SubmissionStatus = isVerificationPassing(verification) ? 'passed' : 'failed';
  const updated: Submission = {
    ...submission,
    verification,
    status,
    updatedAt: nowIso(),
  };
  submissions.set(submissionId, updated);
  return updated;
};

export const requestApproval = (submissionId: string): Submission => {
  const submission = requireSubmission(submissionId);
  if (submission.status !== 'passed') {
    throw new Error(`Submission ${submissionId} must be passed before requesting approval`);
  }
  const updated: Submission = {
    ...submission,
    status: 'awaiting_approval',
    updatedAt: nowIso(),
  };
  submissions.set(submissionId, updated);
  return updated;
};

export const approveSubmission = (submissionId: string): Submission => {
  const submission = requireSubmission(submissionId);
  if (submission.status !== 'awaiting_approval') {
    throw new Error(`Submission ${submissionId} is not awaiting approval`);
  }
  const updated: Submission = {
    ...submission,
    status: 'approved',
    updatedAt: nowIso(),
  };
  submissions.set(submissionId, updated);
  return updated;
};

export const rejectSubmission = (submissionId: string): Submission => {
  const submission = requireSubmission(submissionId);
  const updated: Submission = {
    ...submission,
    status: 'rejected',
    updatedAt: nowIso(),
  };
  submissions.set(submissionId, updated);
  return updated;
};

export const isVerificationPassing = (verification: SubmissionVerification): boolean =>
  verification.healthChecksPassed &&
  verification.logScanPassed &&
  verification.visionDiffRatio <= verification.visionDiffThreshold;

export const resetSubmissionsStore = (): void => {
  submissions.clear();
};
