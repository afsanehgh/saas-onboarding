import { AxiosError, AxiosHeaders } from 'axios';
import { getErrorMessage } from '@/lib/api/client';

function axiosError(status: number, data: unknown) {
  const config = { headers: new AxiosHeaders() };
  return new AxiosError('Request failed', 'ERR_BAD_RESPONSE', config, {}, {
    status,
    statusText: '',
    headers: {},
    config,
    data,
  });
}

describe('getErrorMessage', () => {
  it('prefers the first validation error from Laravel', () => {
    const err = axiosError(422, { message: 'The given data was invalid.', errors: { email: ['Email is taken.'] } });
    expect(getErrorMessage(err)).toBe('Email is taken.');
  });

  it('falls back to the API message', () => {
    expect(getErrorMessage(axiosError(409, { message: 'Start onboarding first.' }))).toBe('Start onboarding first.');
  });

  it('explains when the API cannot be reached', () => {
    const err = new AxiosError('Network Error', 'ERR_NETWORK', { headers: new AxiosHeaders() });
    expect(getErrorMessage(err)).toMatch(/Cannot reach the API/);
  });
});
