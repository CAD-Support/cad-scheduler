/**
 * CAD Scheduler — API
 * Bookly AJAX bridge and HTTP transport.
 *
 * @module cad-api
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;

  if (!CAD) {
    throw new Error('cad-core.js must be loaded before cad-api.js');
  }

  /** @type {number} */
  const MAX_ERROR_BODY_LENGTH = 500;

  /**
   * @param {string} text
   * @param {number} [maxLength]
   * @returns {string}
   */
  function truncateBody(text, maxLength = MAX_ERROR_BODY_LENGTH) {
    if (text.length <= maxLength) {
      return text;
    }

    return `${text.slice(0, maxLength)}…`;
  }

  /**
   * @param {Response} response
   * @returns {Promise<string>}
   */
  async function readResponseText(response) {
    try {
      return await response.text();
    } catch (error) {
      CAD.Logger.warn('Unable to read response body:', error);
      return '';
    }
  }

  /**
   * @param {Response} response
   * @param {string} body
   * @returns {Error}
   */
  function createHttpError(response, body) {
    const detail = truncateBody(body.trim());
    const message = detail
      ? `Request failed: ${response.status} ${response.statusText} — ${detail}`
      : `Request failed: ${response.status} ${response.statusText}`;

    return new Error(message);
  }

  /**
   * @param {Response} response
   * @param {string} body
   * @returns {unknown}
   */
  function parseJsonBody(response, body) {
    const trimmed = body.trim();

    if (!trimmed) {
      throw new Error(
        `Request succeeded (${response.status}) but response body was empty`
      );
    }

    try {
      return JSON.parse(trimmed);
    } catch (parseError) {
      const detail = truncateBody(trimmed);
      const reason =
        parseError instanceof Error ? parseError.message : 'Unknown parse error';

      throw new Error(
        `Request succeeded (${response.status}) but response was not valid JSON (${reason}) — ${detail}`
      );
    }
  }

  /**
   * @param {Response} response
   * @returns {Promise<unknown>}
   */
  async function parseJsonResponse(response) {
    const body = await readResponseText(response);
    return parseJsonBody(response, body);
  }

  const api = Object.freeze({
    /**
     * Send a POST request to the WordPress AJAX endpoint.
     *
     * @param {string} action WordPress AJAX action name.
     * @param {Record<string, unknown>} [data] Request payload fields.
     * @returns {Promise<unknown>}
     */
    async request(action, data = {}) {
      const body = new FormData();
      body.append('action', action);
      body.append('nonce', String(CAD.Config.get('nonce') ?? ''));

      Object.entries(data).forEach(([key, value]) => {
        body.append(key, String(value ?? ''));
      });

      const response = await fetch(String(CAD.Config.get('ajaxUrl') ?? ''), {
        method: 'POST',
        body,
        credentials: 'same-origin',
      });

      if (!response.ok) {
        const errorBody = await readResponseText(response);
        throw createHttpError(response, errorBody);
      }

      return parseJsonResponse(response);
    },

    /**
     * Retrieve appointments for the scheduler on a given date.
     *
     * @param {string} date Schedule date (YYYY-MM-DD).
     * @returns {Promise<unknown>}
     */
    getSchedule(date) {
      return api.request('cad_get_schedule', { date });
    },
  });

  CAD.Modules.register('api', api);
  CAD.API = api;
})(typeof window !== 'undefined' ? window : globalThis);
