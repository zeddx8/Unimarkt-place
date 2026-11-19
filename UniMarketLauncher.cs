using System;
using System.Diagnostics;
using System.IO;

namespace UniMarket
{
    class Program
    {
        static int Main(string[] args)
        {
            Console.Title = "UniMarket Ghana - Commercial Launcher";
            Console.ForegroundColor = ConsoleColor.Green;
            Console.WriteLine("===================================================================");
            Console.WriteLine("   UniMarket Ghana -- Commercial Marketplace Launcher (.exe)");
            Console.WriteLine("===================================================================");
            Console.ResetColor();

            string appDir = AppDomain.CurrentDomain.BaseDirectory;
            string deployBat = Path.Combine(appDir, "deploy.bat");

            if (!File.Exists(deployBat))
            {
                Console.ForegroundColor = ConsoleColor.Red;
                Console.WriteLine("[ERROR] deploy.bat was not found in directory: " + appDir);
                Console.ResetColor();
                Console.WriteLine("Press any key to exit...");
                Console.ReadKey();
                return 1;
            }

            try
            {
                ProcessStartInfo psi = new ProcessStartInfo
                {
                    FileName = "cmd.exe",
                    Arguments = "/c \"" + deployBat + "\"",
                    WorkingDirectory = appDir,
                    UseShellExecute = false
                };

                using (Process proc = Process.Start(psi))
                {
                    proc.WaitForExit();
                    return proc.ExitCode;
                }
            }
            catch (Exception ex)
            {
                Console.ForegroundColor = ConsoleColor.Red;
                Console.WriteLine("[ERROR] Failed to start deployment: " + ex.Message);
                Console.ResetColor();
                Console.WriteLine("Press any key to exit...");
                Console.ReadKey();
                return 1;
            }
        }
    }
}
