import { randomUUID } from 'crypto';

export type AttachmentKind = 'logs' | 'screenshots' | 'bundle';

export interface AttachmentFile {
  name: string;
  contentType: string;
  sizeBytes: number;
  checksum?: string;
}

export interface AttachmentBundle {
  id: string;
  jobId: string;
  submissionId?: string;
  kind: AttachmentKind;
  files: AttachmentFile[];
  createdAt: string;
  updatedAt: string;
}

export interface CreateAttachmentBundleInput {
  jobId: string;
  submissionId?: string;
  kind?: AttachmentKind;
  files?: AttachmentFile[];
}

const bundles = new Map<string, AttachmentBundle>();

const nowIso = (): string => new Date().toISOString();

export const createAttachmentBundle = (input: CreateAttachmentBundleInput): AttachmentBundle => {
  const timestamp = nowIso();
  const bundle: AttachmentBundle = {
    id: randomUUID(),
    jobId: input.jobId,
    submissionId: input.submissionId,
    kind: input.kind ?? 'bundle',
    files: input.files ?? [],
    createdAt: timestamp,
    updatedAt: timestamp,
  };
  bundles.set(bundle.id, bundle);
  return bundle;
};

export const listAttachmentBundles = (
  filter?: Partial<Pick<AttachmentBundle, 'jobId' | 'submissionId' | 'kind'>>,
): AttachmentBundle[] => {
  const values = Array.from(bundles.values());
  if (!filter) {
    return values;
  }
  return values.filter((bundle) => {
    if (filter.jobId && bundle.jobId !== filter.jobId) {
      return false;
    }
    if (filter.submissionId && bundle.submissionId !== filter.submissionId) {
      return false;
    }
    if (filter.kind && bundle.kind !== filter.kind) {
      return false;
    }
    return true;
  });
};

export const getAttachmentBundle = (bundleId: string): AttachmentBundle | undefined => bundles.get(bundleId);

export const requireAttachmentBundle = (bundleId: string): AttachmentBundle => {
  const bundle = getAttachmentBundle(bundleId);
  if (!bundle) {
    throw new Error(`Attachment bundle ${bundleId} not found`);
  }
  return bundle;
};

export const addAttachmentFiles = (bundleId: string, files: AttachmentFile[]): AttachmentBundle => {
  const bundle = requireAttachmentBundle(bundleId);
  const updated: AttachmentBundle = {
    ...bundle,
    files: [...bundle.files, ...files],
    updatedAt: nowIso(),
  };
  bundles.set(bundleId, updated);
  return updated;
};

export const removeAttachmentBundle = (bundleId: string): void => {
  bundles.delete(bundleId);
};

export const resetAttachmentsStore = (): void => {
  bundles.clear();
};
