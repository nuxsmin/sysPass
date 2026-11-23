export interface InstallEnvironment {
  dbHost: string;
  dbName: string;
  dbPassword: string;
  siteLang: string;
}

function required(name: string): string {
  const value = process.env[name];

  if (!value) {
    throw new Error(`${name} is required for the isolated browser installer`);
  }

  return value;
}

/**
 * Runner-owned installer inputs. Secrets are passed through the process
 * environment, never through process arguments or committed fixtures.
 */
export function getInstallEnvironment(): InstallEnvironment {
  return {
    dbHost: required('SP_UI_INSTALL_DB_HOST'),
    dbName: required('SP_UI_INSTALL_DB_NAME'),
    dbPassword: required('SP_UI_INSTALL_DB_PASSWORD'),
    siteLang: process.env.SP_UI_INSTALL_SITE_LANG ?? 'en_US',
  };
}
