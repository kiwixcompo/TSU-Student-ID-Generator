import { getStudentById, getAdminSession, markIdGenerated } from '@/lib/actions';
import { redirect } from 'next/navigation';
import IdCardClient from './IdCardClient';

export default async function IdCardPage({ params }: { params: Promise<{ reg: string }> }) {
  const session = await getAdminSession();
  
  if (!session) {
    redirect('/admin');
  }

  // Await the params object before accessing properties
  const { reg } = await params;
  const decodedId = decodeURIComponent(reg);
  // We use getStudentById since we passed student.id
  const student = await getStudentById(decodedId);

  if (!student) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900">Student Not Found</h2>
          <p className="mt-2 text-gray-600">The registration number does not exist.</p>
        </div>
      </div>
    );
  }

  // Security check: Only allow SuperAdmin or the admin managing the specific programme
  if (session.programme_managed !== 'SuperAdmin' && session.programme_managed !== student.programme) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900">Unauthorized</h2>
          <p className="mt-2 text-gray-600">You do not have permission to view ID cards for this programme.</p>
        </div>
      </div>
    );
  }

  // Pass necessary environment variable base URL to the client
  // Using an explicit placeholder since we don't have APP_URL guaranteed
  const baseUrl = process.env.APP_URL || (typeof window !== 'undefined' ? window.location.origin : 'https://example.com');
  
  return (
    <IdCardClient student={student} baseUrl={baseUrl} />
  );
}
