'use server';

import { registerStudent, getAdmin, getStudents, getStudentByReg, getStudentById as dbGetStudentById, updateStudentStatus, deleteStudentById, updateStudentPassword, updateStudentNote, type Student } from './db';
import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import { revalidatePath } from 'next/cache';

export async function submitStudentRegistration(formData: FormData) {
  try {
    const programme = formData.get('programme') as 'Sandwich' | 'IDELL';
    const first_name = formData.get('first_name') as string;
    const middle_name = formData.get('middle_name') as string;
    const last_name = formData.get('last_name') as string;
    const reg_number = formData.get('reg_number') as string;
    const blood_group = formData.get('blood_group') as string;
    const faculty = formData.get('faculty') as string;
    const department = formData.get('department') as string;
    const course_of_study = formData.get('course_of_study') as string;
    
    // We expect the frontend to pass the image as a base64 string
    const passport_photo = formData.get('passport_photo') as string;
    
    if (!programme || !first_name || !last_name || !reg_number || !blood_group || !faculty || !department || !passport_photo) {
      throw new Error('All required fields must be filled.');
    }

    registerStudent({
      programme,
      first_name,
      middle_name,
      last_name,
      reg_number,
      blood_group,
      faculty,
      department,
      course_of_study,
      passport_photo
    });

    return { success: true, message: 'Registration successful! Please wait for ID generation.' };
  } catch (error: any) {
    return { success: false, error: error.message };
  }
}

export async function adminLogin(formData: FormData) {
  const username = formData.get('username') as string;
  const password = formData.get('password') as string;

  const admin = getAdmin(username);
  
  if (admin && admin.password_hash === password) { // Mock hash comparison
    (await cookies()).set('admin_session', JSON.stringify({
      username: admin.username,
      programme_managed: admin.programme_managed
    }), {
      httpOnly: true,
      secure: true,
      sameSite: 'none',
      path: '/'
    });
    return { success: true };
  } else {
    return { success: false, error: 'Invalid username or password' };
  }
}

export async function adminLogout() {
  (await cookies()).delete('admin_session');
  redirect('/admin');
}

export async function studentLogin(formData: FormData) {
  const reg_number = formData.get('reg_number') as string;
  const password = formData.get('password') as string;

  const student = getStudentByReg(reg_number);
  
  const studentPassword = student?.password || student?.reg_number;

  // Check login requirements
  if (student && studentPassword === password && reg_number.startsWith('TSU') && reg_number.includes('/')) {
    (await cookies()).set('student_session', JSON.stringify({
      reg_number: student.reg_number
    }), {
      httpOnly: true,
      secure: true,
      sameSite: 'none',
      path: '/'
    });
    return { success: true };
  } else {
    return { success: false, error: 'Invalid registration number or password' };
  }
}

export async function studentLogout() {
  (await cookies()).delete('student_session');
  redirect('/student/login');
}

export async function getStudentSession() {
  const session = (await cookies()).get('student_session')?.value;
  if (session) {
    return JSON.parse(session);
  }
  return null;
}

export async function getAdminSession() {
  const session = (await cookies()).get('admin_session')?.value;
  if (session) {
    return JSON.parse(session);
  }
  return null;
}

export async function fetchStudentsForAdmin() {
  const session = await getAdminSession();
  if (!session) {
    throw new Error('Unauthorized');
  }
  return getStudents(session.programme_managed);
}

export async function markIdGenerated(reg_number: string) {
  const session = await getAdminSession();
  if (!session) {
    throw new Error('Unauthorized');
  }
  updateStudentStatus(reg_number, 'id_generated');
  return { success: true };
}

export async function getStudentById(id: string) {
  return dbGetStudentById(id);
}

export async function verifyStudent(reg_number: string) {
  const student = getStudentByReg(reg_number);
  return student; // Returns undefined if not found
}

export async function deleteStudent(id: string) {
  const session = await getAdminSession();
  if (!session) throw new Error('Unauthorized');
  deleteStudentById(id);
  revalidatePath('/admin/dashboard');
  return { success: true };
}

export async function changeStudentPassword(id: string, newPassword: string) {
  const session = await getAdminSession();
  if (!session) throw new Error('Unauthorized');
  
  if (!newPassword || newPassword.length < 3) {
     return { success: false, error: 'Password must be at least 3 characters long.' };
  }
  
  updateStudentPassword(id, newPassword);
  revalidatePath('/admin/dashboard');
  return { success: true };
}

export async function updateStudentNoteAction(id: string, note: string) {
  const session = await getAdminSession();
  if (!session) throw new Error('Unauthorized');
  
  updateStudentNote(id, note);
  revalidatePath('/admin/dashboard');
  return { success: true };
}

