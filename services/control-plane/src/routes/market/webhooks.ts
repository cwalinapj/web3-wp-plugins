import { randomUUID } from 'crypto';

export type JobWebhookEventType = 'job.status';

export interface JobWebhookEvent {
  id: string;
  jobId: string;
  status: string;
  eventType: JobWebhookEventType;
  payload?: Record<string, unknown>;
  createdAt: string;
}

export interface WebhookEndpoint {
  id: string;
  url: string;
  secret: string;
  active: boolean;
  createdAt: string;
  updatedAt: string;
}

export type WebhookDeliveryStatus = 'pending' | 'sent' | 'failed';

export interface WebhookDelivery {
  id: string;
  eventId: string;
  endpointId: string;
  status: WebhookDeliveryStatus;
  attempts: number;
  lastAttemptAt?: string;
  responseStatus?: number;
  errorMessage?: string;
}

export interface CreateWebhookEndpointInput {
  url: string;
  secret: string;
}

const endpoints = new Map<string, WebhookEndpoint>();
const events = new Map<string, JobWebhookEvent>();
const deliveries = new Map<string, WebhookDelivery>();

const nowIso = (): string => new Date().toISOString();

export const createWebhookEndpoint = (input: CreateWebhookEndpointInput): WebhookEndpoint => {
  const timestamp = nowIso();
  const endpoint: WebhookEndpoint = {
    id: randomUUID(),
    url: input.url,
    secret: input.secret,
    active: true,
    createdAt: timestamp,
    updatedAt: timestamp,
  };
  endpoints.set(endpoint.id, endpoint);
  return endpoint;
};

export const listWebhookEndpoints = (): WebhookEndpoint[] => Array.from(endpoints.values());

export const getWebhookEndpoint = (endpointId: string): WebhookEndpoint | undefined => endpoints.get(endpointId);

export const updateWebhookEndpoint = (
  endpointId: string,
  updates: Partial<Pick<WebhookEndpoint, 'url' | 'secret' | 'active'>>,
): WebhookEndpoint => {
  const endpoint = getWebhookEndpoint(endpointId);
  if (!endpoint) {
    throw new Error(`Webhook endpoint ${endpointId} not found`);
  }
  const updated: WebhookEndpoint = {
    ...endpoint,
    ...updates,
    updatedAt: nowIso(),
  };
  endpoints.set(endpointId, updated);
  return updated;
};

export const removeWebhookEndpoint = (endpointId: string): void => {
  endpoints.delete(endpointId);
};

export const emitJobStatusEvent = (
  jobId: string,
  status: string,
  payload?: Record<string, unknown>,
): { event: JobWebhookEvent; deliveries: WebhookDelivery[] } => {
  const event: JobWebhookEvent = {
    id: randomUUID(),
    jobId,
    status,
    eventType: 'job.status',
    payload,
    createdAt: nowIso(),
  };
  events.set(event.id, event);

  const createdDeliveries: WebhookDelivery[] = [];
  endpoints.forEach((endpoint) => {
    if (!endpoint.active) {
      return;
    }
    const delivery: WebhookDelivery = {
      id: randomUUID(),
      eventId: event.id,
      endpointId: endpoint.id,
      status: 'pending',
      attempts: 0,
    };
    deliveries.set(delivery.id, delivery);
    createdDeliveries.push(delivery);
  });

  return { event, deliveries: createdDeliveries };
};

export const listDeliveriesForEvent = (eventId: string): WebhookDelivery[] =>
  Array.from(deliveries.values()).filter((delivery) => delivery.eventId === eventId);

export const recordDeliveryAttempt = (
  deliveryId: string,
  result: { success: boolean; responseStatus?: number; errorMessage?: string },
): WebhookDelivery => {
  const delivery = deliveries.get(deliveryId);
  if (!delivery) {
    throw new Error(`Webhook delivery ${deliveryId} not found`);
  }
  const updated: WebhookDelivery = {
    ...delivery,
    status: result.success ? 'sent' : 'failed',
    attempts: delivery.attempts + 1,
    lastAttemptAt: nowIso(),
    responseStatus: result.responseStatus,
    errorMessage: result.errorMessage,
  };
  deliveries.set(deliveryId, updated);
  return updated;
};

export const resetWebhooksStore = (): void => {
  endpoints.clear();
  events.clear();
  deliveries.clear();
};
