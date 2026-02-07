import { randomUUID } from 'crypto';

export interface HelperProfile {
  id: string;
  displayName: string;
  bio?: string;
  skills: string[];
  reputationScore: number;
  jobsCompleted: number;
  jobsFailed: number;
  ratingAverage: number;
  ratingCount: number;
  contact?: {
    email?: string;
    website?: string;
  };
  createdAt: string;
  updatedAt: string;
}

export interface CreateHelperInput {
  displayName: string;
  bio?: string;
  skills?: string[];
  contact?: {
    email?: string;
    website?: string;
  };
}

export interface UpdateHelperInput {
  displayName?: string;
  bio?: string;
  skills?: string[];
  contact?: {
    email?: string;
    website?: string;
  };
}

export type HelperOutcome = 'success' | 'failure';

const helpers = new Map<string, HelperProfile>();

const nowIso = (): string => new Date().toISOString();

const clamp = (value: number, min: number, max: number): number => Math.max(min, Math.min(max, value));

export const createHelper = (input: CreateHelperInput): HelperProfile => {
  const timestamp = nowIso();
  const helper: HelperProfile = {
    id: randomUUID(),
    displayName: input.displayName,
    bio: input.bio,
    skills: input.skills ?? [],
    reputationScore: 0,
    jobsCompleted: 0,
    jobsFailed: 0,
    ratingAverage: 0,
    ratingCount: 0,
    contact: input.contact,
    createdAt: timestamp,
    updatedAt: timestamp,
  };

  helpers.set(helper.id, helper);
  return helper;
};

export const listHelpers = (filter?: { minReputation?: number }): HelperProfile[] => {
  const values = Array.from(helpers.values());
  if (!filter || filter.minReputation === undefined) {
    return values;
  }
  return values.filter((helper) => helper.reputationScore >= filter.minReputation);
};

export const getHelper = (helperId: string): HelperProfile | undefined => helpers.get(helperId);

export const requireHelper = (helperId: string): HelperProfile => {
  const helper = getHelper(helperId);
  if (!helper) {
    throw new Error(`Helper ${helperId} not found`);
  }
  return helper;
};

export const updateHelper = (helperId: string, updates: UpdateHelperInput): HelperProfile => {
  const helper = requireHelper(helperId);
  const updated: HelperProfile = {
    ...helper,
    ...updates,
    skills: updates.skills ?? helper.skills,
    contact: updates.contact ?? helper.contact,
    updatedAt: nowIso(),
  };
  helpers.set(helperId, updated);
  return updated;
};

export const recordHelperOutcome = (
  helperId: string,
  outcome: HelperOutcome,
  rating?: number,
): HelperProfile => {
  const helper = requireHelper(helperId);
  const ratingCount = helper.ratingCount + (rating !== undefined ? 1 : 0);
  const ratingAverage =
    rating !== undefined
      ? (helper.ratingAverage * helper.ratingCount + clamp(rating, 1, 5)) / ratingCount
      : helper.ratingAverage;
  const jobsCompleted = helper.jobsCompleted + (outcome === 'success' ? 1 : 0);
  const jobsFailed = helper.jobsFailed + (outcome === 'failure' ? 1 : 0);
  const reputationScore = clamp((jobsCompleted - jobsFailed) * 10 + ratingAverage * 5, 0, 100);

  const updated: HelperProfile = {
    ...helper,
    ratingAverage,
    ratingCount,
    jobsCompleted,
    jobsFailed,
    reputationScore,
    updatedAt: nowIso(),
  };
  helpers.set(helperId, updated);
  return updated;
};

export const resetHelpersStore = (): void => {
  helpers.clear();
};
