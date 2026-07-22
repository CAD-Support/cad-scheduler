/**
 * CAD Scheduler — Core
 * Framework foundation: configuration, state, events, logging, and module registry.
 *
 * @module cad-core
 */
(function (global) {
  'use strict';

  /** @type {Record<string, unknown>} */
  const CAD = global.CAD || {};

  /** @type {CADVersion} */
  CAD.VERSION = Object.freeze({
    major: 1,
    minor: 0,
    patch: 0,
    build: '2026.07.22',
  });

  /** @type {Record<string, unknown>} */
  const defaultConfig = {
    debug: false,
  };

  /** @type {Record<string, unknown>} */
  let configData = clone(defaultConfig);

  /** @type {Record<string, unknown>} */
  const defaultState = {};

  /** @type {Record<string, unknown>} */
  let stateData = clone(defaultState);

  /** @type {Map<string, Set<EventHandler>>} */
  const listeners = new Map();

  /** @type {Record<string, unknown>} */
  const moduleRegistry = Object.create(null);

  /** @type {ReadyCallback[]} */
  const readyQueue = [];

  /** @type {boolean} */
  let isReady = false;

  /** @type {boolean} */
  let initialized = false;

  /** @type {string[]} */
  const FRAMEWORK_SHORTCUTS = ['API'];

  /**
   * @param {unknown} value
   * @returns {value is Record<string, unknown>}
   */
  function isPlainObject(value) {
    return (
      typeof value === 'object' &&
      value !== null &&
      !Array.isArray(value) &&
      Object.getPrototypeOf(value) === Object.prototype
    );
  }

  /**
   * @param {unknown} value
   * @returns {unknown}
   */
  function clone(value) {
    if (Array.isArray(value)) {
      return value.map((item) => clone(item));
    }

    if (isPlainObject(value)) {
      /** @type {Record<string, unknown>} */
      const copy = {};
      Object.keys(value).forEach((key) => {
        copy[key] = clone(value[key]);
      });
      return copy;
    }

    return value;
  }

  /**
   * @param {Record<string, unknown>} target
   * @param {...Record<string, unknown>} sources
   * @returns {Record<string, unknown>}
   */
  function mergeObjects(target, ...sources) {
    sources.forEach((source) => {
      if (!isPlainObject(source)) {
        return;
      }

      Object.keys(source).forEach((key) => {
        const sourceValue = source[key];
        const targetValue = target[key];

        if (isPlainObject(sourceValue) && isPlainObject(targetValue)) {
          mergeObjects(/** @type {Record<string, unknown>} */ (targetValue), sourceValue);
        } else {
          target[key] = clone(sourceValue);
        }
      });
    });

    return target;
  }

  /**
   * @param {unknown} key
   */
  function validateStoreKey(key) {
    if (typeof key !== 'string' || key.length === 0) {
      throw new TypeError('Key must be a non-empty string');
    }
  }

  /**
   * @param {unknown} values
   */
  function validateMergeInput(values) {
    if (!isPlainObject(values)) {
      throw new TypeError('Merge input must be a plain object');
    }
  }

  /**
   * @param {unknown} value
   * @returns {unknown}
   */
  function storeValue(value) {
    if (Array.isArray(value) || isPlainObject(value)) {
      return clone(value);
    }

    return value;
  }

  /**
   * Remove top-level module shortcuts from the CAD namespace.
   */
  function clearFrameworkShortcuts() {
    FRAMEWORK_SHORTCUTS.forEach((key) => {
      if (Object.prototype.hasOwnProperty.call(CAD, key)) {
        delete CAD[key];
      }
    });
  }

  CAD.Utils = Object.freeze({
    clone,
    merge: mergeObjects,
    isPlainObject,
    isFunction(value) {
      return typeof value === 'function';
    },
    noop() {},
    defer(callback, delay = 0) {
      return global.setTimeout(callback, delay);
    },
  });

  CAD.Logger = Object.freeze({
    /**
     * @param {...unknown} args
     */
    log(...args) {
      if (CAD.Config.get('debug')) {
        console.log('[CAD]', ...args);
      }
    },

    /**
     * @param {...unknown} args
     */
    warn(...args) {
      if (CAD.Config.get('debug')) {
        console.warn('[CAD]', ...args);
      }
    },

    /**
     * @param {...unknown} args
     */
    error(...args) {
      console.error('[CAD]', ...args);
    },
  });

  CAD.Config = Object.freeze({
    /**
     * @param {string} key
     * @returns {unknown}
     */
    get(key) {
      return configData[key];
    },

    /**
     * @param {string} key
     * @param {unknown} value
     * @returns {unknown}
     */
    set(key, value) {
      validateStoreKey(key);
      const stored = storeValue(value);
      configData[key] = stored;
      return stored;
    },

    /**
     * @param {string} key
     * @returns {boolean}
     */
    has(key) {
      return Object.prototype.hasOwnProperty.call(configData, key);
    },

    /**
     * @returns {Record<string, unknown>}
     */
    getAll() {
      return clone(configData);
    },

    /**
     * @param {Record<string, unknown>} values
     */
    merge(values) {
      validateMergeInput(values);
      mergeObjects(configData, values);
    },

    reset() {
      configData = clone(defaultConfig);
    },
  });

  CAD.State = Object.freeze({
    /**
     * @param {string} key
     * @returns {unknown}
     */
    get(key) {
      return stateData[key];
    },

    /**
     * @param {string} key
     * @param {unknown} value
     * @returns {unknown}
     */
    set(key, value) {
      validateStoreKey(key);
      const stored = storeValue(value);
      stateData[key] = stored;
      return stored;
    },

    /**
     * @param {string} key
     * @returns {boolean}
     */
    has(key) {
      return Object.prototype.hasOwnProperty.call(stateData, key);
    },

    /**
     * @returns {Record<string, unknown>}
     */
    getAll() {
      return clone(stateData);
    },

    /**
     * @param {Record<string, unknown>} values
     */
    merge(values) {
      validateMergeInput(values);
      mergeObjects(stateData, values);
    },

    reset() {
      stateData = clone(defaultState);
    },
  });

  CAD.Events = Object.freeze({
    /**
     * @param {string} event
     * @param {EventHandler} handler
     */
    on(event, handler) {
      if (!listeners.has(event)) {
        listeners.set(event, new Set());
      }

      listeners.get(event).add(handler);
    },

    /**
     * @param {string} event
     * @param {EventHandler} [handler]
     */
    off(event, handler) {
      if (!listeners.has(event)) {
        return;
      }

      if (handler) {
        listeners.get(event).delete(handler);
        return;
      }

      listeners.delete(event);
    },

    /**
     * @param {string} event
     * @param {unknown} [payload]
     */
    emit(event, payload) {
      const handlers = listeners.get(event);

      if (!handlers) {
        return;
      }

      handlers.forEach((handler) => {
        try {
          handler(payload);
        } catch (error) {
          CAD.Logger.error(`Event handler failed for "${event}":`, error);
        }
      });
    },
  });

  CAD.Modules = Object.freeze({
    /**
     * @param {string} name
     * @param {unknown} module
     * @returns {unknown}
     */
    register(name, module) {
      if (typeof name !== 'string' || name.length === 0) {
        throw new TypeError('Module name must be a non-empty string');
      }

      moduleRegistry[name] = module;
      CAD.Events.emit('module:register', { name, module });
      return module;
    },

    /**
     * @param {string} name
     * @returns {unknown}
     */
    get(name) {
      return moduleRegistry[name];
    },

    /**
     * @param {string} name
     * @returns {boolean}
     */
    has(name) {
      return Object.prototype.hasOwnProperty.call(moduleRegistry, name);
    },

    /**
     * @param {string} name
     * @returns {unknown}
     */
    unregister(name) {
      const module = moduleRegistry[name];
      delete moduleRegistry[name];
      CAD.Events.emit('module:unregister', { name, module });
      return module;
    },

    /**
     * @returns {string[]}
     */
    names() {
      return Object.keys(moduleRegistry);
    },
  });

  /**
   * @param {ReadyCallback} callback
   * @returns {typeof CAD}
   */
  CAD.ready = function ready(callback) {
    if (typeof callback !== 'function') {
      throw new TypeError('CAD.ready expects a function');
    }

    if (isReady) {
      callback(CAD);
      return CAD;
    }

    readyQueue.push(callback);
    return CAD;
  };

  /**
   * @param {Record<string, unknown>} [options]
   * @returns {typeof CAD}
   */
  CAD.init = function init(options = {}) {
    if (initialized) {
      CAD.Logger.warn('CAD.init called more than once');
      return CAD;
    }

    CAD.Config.merge(options);
    initialized = true;
    CAD.Events.emit('cad:init', { config: CAD.Config.getAll() });

    isReady = true;

    while (readyQueue.length > 0) {
      const callback = readyQueue.shift();

      try {
        callback(CAD);
      } catch (error) {
        CAD.Logger.error('Ready callback failed:', error);
      }
    }

    return CAD;
  };

  /**
   * @returns {typeof CAD}
   */
  CAD.destroy = function destroy() {
    CAD.Events.emit('cad:destroy');

    listeners.clear();
    CAD.State.reset();
    CAD.Config.reset();

    Object.keys(moduleRegistry).forEach((name) => {
      delete moduleRegistry[name];
    });

    clearFrameworkShortcuts();

    readyQueue.length = 0;
    isReady = false;
    initialized = false;

    return CAD;
  };

  global.CAD = CAD;
})(typeof window !== 'undefined' ? window : globalThis);

/**
 * @typedef {Object} CADVersion
 * @property {number} major
 * @property {number} minor
 * @property {number} patch
 * @property {string} build
 */

/**
 * @callback EventHandler
 * @param {unknown} payload
 * @returns {void}
 */

/**
 * @callback ReadyCallback
 * @param {typeof CAD} cad
 * @returns {void}
 */
