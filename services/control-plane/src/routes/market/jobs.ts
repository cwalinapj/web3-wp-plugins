import { randomUUID } from 'crypto';

export type JobState =
  | 'open'
  | 'assigned'
  | 'submitted'
  | 'verifying'
  | 'awaiting_approval'
  | 'approved'
  | 'rejected'
  | 'cancelled';

export type JobUrgency = 'low' | 'medium' | 'high';

export interface JobVerificationGate {
  healthChecksPassed: boolean;
  logScanPassed: boolean;
  visionDiffRatio: number;
  visionDiffThreshold: number;
}

export interface Job {
  id: string;
  title: string;
  description: string;
  requesterId: string;
  urgency: JobUrgency;
  stakeAmount: number;
  deadline?: string;
  metadata?: Record<string, string>;
  state: JobState;
  sandboxOnly: true;
  requiresApproval: true;
  helperId?: string;
  submissionId?: string;
  verification?: JobVerificationGate;
  createdAt: string;
  updatedAt: string;
}

export interface CreateJobInput {
  title: string;
  description: string;
  requesterId: string;
  urgency?: JobUrgency;
  stakeAmount?: number;
  deadline?: string;
  metadata?: Record<string, string>;
  visionDiffThreshold?: number;
}

export interface UpdateJobInput {
  title?: string;
  description?: string;
  urgency?: JobUrgency;
  stakeAmount?: number;
  deadline?: string;
  metadata?: Record<string, string>;
  visionDiffThreshold?: number;
}

export const DEFAULT_VISION_DIFF_THRESHOLD = 0.02;

const JOB_STATE_MACHINE: Record<JobState, JobState[]> = {
  open: ['assigned', 'cancelled'],
  assigned: ['submitted', 'cancelled'],
  submitted: ['verifying', 'cancelled'],
  verifying: ['awaiting_approval', 'rejected'],
  awaiting_approval: ['approved', 'rejected'],
  approved: [],
  rejected: [],
  cancelled: [],
};

const jobs = new Map<string, Job>();

const nowIso = (): string => new Date().toISOString();

export const listJobs = (filter?: Partial<Pick<Job, 'state' | 'helperId' | 'requesterId'>>): Job[] => {
  const values = Array.from(jobs.values());
  if (!filter) {
    return values;
  }

  return values.filter((job) => {
    if (filter.state && job.state !== filter.state) {
      return false;
    }
    if (filter.helperId && job.helperId !== filter.helperId) {
      return false;
    }
    if (filter.requesterId && job.requesterId !== filter.requesterId) {
      return false;
    }
    return true;
  });
};

export const getJob = (jobId: string): Job | undefined => jobs.get(jobId);

export const requireJob = (jobId: string): Job => {
  const job = getJob(jobId);
  if (!job) {
    throw new Error(`Job ${jobId} not found`);
  }
  return job;
};

export const createJob = (input: CreateJobInput): Job => {
  const timestamp = nowIso();
  const job: Job = {
    id: randomUUID(),
    title: input.title,
    description: input.description,
    requesterId: input.requesterId,
    urgency: input.urgency ?? 'medium',
    stakeAmount: input.stakeAmount ?? 0,
    deadline: input.deadline,
    metadata: input.metadata,
    state: 'open',
    sandboxOnly: true,
    requiresApproval: true,
    verification: input.visionDiffThreshold
      ? {
          healthChecksPassed: false,
          logScanPassed: false,
          visionDiffRatio: 0,
          visionDiffThreshold: input.visionDiffThreshold,
        }
      : undefined,
    createdAt: timestamp,
    updatedAt: timestamp,
  };

  if (!job.verification) {
    job.verification = {
      healthChecksPassed: false,
      logScanPassed: false,
      visionDiffRatio: 0,
      visionDiffThreshold: input.visionDiffThreshold ?? DEFAULT_VISION_DIFF_THRESHOLD,
    };
  }

  jobs.set(job.id, job);
  return job;
};

export const updateJob = (jobId: string, updates: UpdateJobInput): Job => {
  const job = requireJob(jobId);
  const updated: Job = {
    ...job,
    ...updates,
    verification: job.verification
      ? {
          ...job.verification,
          visionDiffThreshold: updates.visionDiffThreshold ?? job.verification.visionDiffThreshold,
        }
      : updates.visionDiffThreshold
        ? {
            healthChecksPassed: false,
            logScanPassed: false,
            visionDiffRatio: 0,
            visionDiffThreshold: updates.visionDiffThreshold,
          }
        : job.verification,
    updatedAt: nowIso(),
  };

  jobs.set(jobId, updated);
  return updated;
};

export const deleteJob = (jobId: string): void => {
  jobs.delete(jobId);
};

export const transitionJob = (jobId: string, nextState: JobState): Job => {
  const job = requireJob(jobId);
  const allowed = JOB_STATE_MACHINE[job.state];
  if (!allowed.includes(nextState)) {
    throw new Error(`Cannot transition job ${jobId} from ${job.state} to ${nextState}`);
  }
  const updated = {
    ...job,
    state: nextState,
    updatedAt: nowIso(),
  };
  jobs.set(jobId, updated);
  return updated;
};

export const assignHelper = (jobId: string, helperId: string): Job => {
  const job = transitionJob(jobId, 'assigned');
  const updated = {
    ...job,
    helperId,
    updatedAt: nowIso(),
  };
  jobs.set(jobId, updated);
  return updated;
};

export const recordSubmission = (jobId: string, submissionId: string): Job => {
  const job = transitionJob(jobId, 'submitted');
  const updated = {
    ...job,
    submissionId,
    updatedAt: nowIso(),
  };
  jobs.set(jobId, updated);
  return updated;
};

export const startVerification = (jobId: string): Job => transitionJob(jobId, 'verifying');

export const recordVerification = (jobId: string, verification: JobVerificationGate): Job => {
  const job = requireJob(jobId);
  const nextState = isVerificationPassing(verification) ? 'awaiting_approval' : 'rejected';
  const allowed = JOB_STATE_MACHINE[job.state];
  if (!allowed.includes(nextState)) {
    throw new Error(`Cannot record verification for job ${jobId} in state ${job.state}`);
  }

  const updated = {
    ...job,
    verification,
    state: nextState,
    updatedAt: nowIso(),
  };
  jobs.set(jobId, updated);
  return updated;
};

export const approveJob = (jobId: string): Job => transitionJob(jobId, 'approved');

export const rejectJob = (jobId: string): Job => transitionJob(jobId, 'rejected');

export const cancelJob = (jobId: string): Job => transitionJob(jobId, 'cancelled');

export const isVerificationPassing = (verification: JobVerificationGate): boolean =>
  verification.healthChecksPassed &&
  verification.logScanPassed &&
  verification.visionDiffRatio <= verification.visionDiffThreshold;

export const resetJobsStore = (): void => {
  jobs.clear();
};
